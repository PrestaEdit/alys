<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Treatment;
use App\Services\ActiveProfile;
use Carbon\Carbon;

class CalendarService
{
    private function profileDates(): array
    {
        $profile = app(ActiveProfile::class)->get();
        return [
            'start' => $profile?->treatment_start,
            'end'   => $profile?->treatment_end,
        ];
    }

    public function getDaysRemaining(Carbon $from): ?int
    {
        $end = $this->profileDates()['end'];
        if (!$end) return null;
        return (int) $from->copy()->startOfDay()->diffInDays($end->copy()->startOfDay(), false);
    }

    public function getProgressPercent(Carbon $from): ?int
    {
        ['start' => $start, 'end' => $end] = $this->profileDates();
        if (!$start || !$end) return null;
        $totalDays = $start->diffInDays($end);
        if ($totalDays === 0) return 0;
        $elapsed = $start->diffInDays($from->copy()->startOfDay());
        return (int) min(100, max(0, round(($elapsed / $totalDays) * 100)));
    }

    public function getCounters(Carbon $from): array
    {
        $fromDate = $from->toDateString();

        $treatments = Treatment::active()->whereIn('name', ['Hôpital', 'VCR', 'IT MTTX', 'MTX'])
            ->withCount(['calendarEvents as future_count' => fn($q) => $q
                ->whereDate('scheduled_date', '>', $fromDate)
                ->where('is_cancelled', false)
            ])
            ->get()
            ->keyBy('name');

        return [
            'hospital' => (int) ($treatments->get('Hôpital')?->future_count ?? 0),
            'vcr'      => (int) ($treatments->get('VCR')?->future_count ?? 0),
            'it_mttx'  => (int) ($treatments->get('IT MTTX')?->future_count ?? 0),
            'mtx'      => (int) ($treatments->get('MTX')?->future_count ?? 0),
        ];
    }

    public function getWidgets(Carbon $from): array
    {
        $fromDate = $from->toDateString();

        $treatments = Treatment::active()->where('show_widget', true)
            ->withCount(['calendarEvents as future_count' => fn($q) => $q
                ->whereDate('scheduled_date', '>', $fromDate)
                ->where('is_cancelled', false)
            ])
            ->get()
            ->sortBy(fn($t) => $t->name === 'Hôpital' ? 0 : 1);

        return $treatments->map(fn($t) => [
            'display_name' => $t->displayName(),
            'count'        => (int) ($t->future_count ?? 0),
            'icon'         => $t->widget_icon ?? '💊',
            'color'        => $t->color,
        ])->values()->toArray();
    }

    public function getNextHospitalVisit(Carbon $from): ?Carbon
    {
        $hopital = Treatment::active()->where('name', 'Hôpital')->first();
        if (!$hopital) return null;

        $event = CalendarEvent::where('treatment_id', $hopital->id)
            ->whereDate('scheduled_date', '>', $from->toDateString())
            ->where('is_cancelled', false)
            ->orderBy('scheduled_date')
            ->first();

        return $event ? Carbon::parse($event->scheduled_date) : null;
    }

    public function getEventsForDay(Carbon $date): array
    {
        $dateStr = $date->toDateString();
        $events = [];

        // Daily treatments (not stored in calendar_events)
        $dailyTreatments = Treatment::active()->where('type', 'daily')->with('posologyHistory')->get();
        foreach ($dailyTreatments as $treatment) {
            $events[] = [
                'id'              => null,
                'treatment_id'    => $treatment->id,
                'name'            => $treatment->name,
                'display_name'    => $treatment->displayName(),
                'commercial_name' => $treatment->commercial_name,
                'type'            => 'daily',
                'unit'            => $treatment->unit,
                'dose'            => $this->getDoseForDate($treatment, $date),
                'color'           => $treatment->color,
                'requires_fasting' => false,
                'can_move'        => false,
                'moved'           => false,
            ];
        }

        // Scheduled calendar events for this day
        $calendarEvents = CalendarEvent::with('treatment')
            ->whereDate('scheduled_date', $dateStr)
            ->where('is_cancelled', false)
            ->get();

        foreach ($calendarEvents as $event) {
            $events[] = [
                'id'              => $event->id,
                'treatment_id'    => $event->treatment_id,
                'name'            => $event->treatment->name,
                'display_name'    => $event->treatment->displayName(),
                'commercial_name' => $event->treatment->commercial_name,
                'type'            => $event->treatment->type,
                'unit'            => $event->treatment->unit,
                'dose'            => $this->formatDose($event->treatment),
                'color'           => $event->treatment->color,
                'requires_fasting' => $event->treatment->requiresFasting(),
                'can_move'        => $event->parent_event_id === null,
                'moved'           => $event->hasMoved(),
                'original_date'   => $event->original_date?->toDateString(),
                'notes'           => $event->notes,
            ];
        }

        return $events;
    }

    public function getEventsForMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $events = CalendarEvent::with('treatment')
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->where('is_cancelled', false)
            ->get()
            ->groupBy(fn($e) => $e->scheduled_date->toDateString());

        return $events->map(fn($dayEvents) => $dayEvents->map(fn($e) => [
            'treatment_id'    => $e->treatment_id,
            'name'            => $e->treatment->name,
            'display_name'    => $e->treatment->displayName(),
            'color'           => $e->treatment->color,
            'requires_fasting' => $e->treatment->requiresFasting(),
        ])->values()->toArray())->toArray();
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function getDoseForDate(Treatment $treatment, Carbon $date): ?string
    {
        $history = $treatment->posologyHistory
            ->filter(fn($h) => $h->started_at->lte($date))
            ->first();

        if ($treatment->hasDayPartDoses()) {
            $morning = (float) ($history?->dose_morning ?? $treatment->dose_morning);
            $noon    = (float) ($history?->dose_noon    ?? $treatment->dose_noon);
            $evening = (float) ($history?->dose_evening ?? $treatment->dose_evening);
            return $this->formatDayParts($treatment, $morning, $noon, $evening);
        }

        $dose = $history ? $history->dose : $treatment->current_dose;
        return $dose !== null ? $this->formatAmount((float) $dose, $treatment->unit) : null;
    }

    private function formatDose(Treatment $treatment): ?string
    {
        if ($treatment->hasDayPartDoses()) {
            return $this->formatDayParts(
                $treatment,
                (float) $treatment->dose_morning,
                (float) $treatment->dose_noon,
                (float) $treatment->dose_evening,
            );
        }

        if ($treatment->current_dose === null) return null;
        return $this->formatAmount((float) $treatment->current_dose, $treatment->unit);
    }

    private function formatDayParts(Treatment $t, float $morning, float $noon, float $evening): string
    {
        $parts = [];
        if ($t->dose_morning !== null) $parts[] = $this->formatAmount($morning, $t->unit) . ' matin';
        if ($t->dose_noon    !== null) $parts[] = $this->formatAmount($noon,    $t->unit) . ' midi';
        if ($t->dose_evening !== null) $parts[] = $this->formatAmount($evening, $t->unit) . ' soir';
        return implode(' · ', $parts);
    }

    private function formatAmount(float $amount, ?string $unit): string
    {
        $formatted = $unit === 'ml'
            ? number_format($amount, 1, ',', '')
            : ($amount == (int) $amount ? (string)(int)$amount : number_format($amount, 2, ',', ''));
        return $unit ? "{$formatted} {$unit}" : $formatted;
    }
}
