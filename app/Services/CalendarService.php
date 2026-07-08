<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\PersonalEvent;
use App\Models\Treatment;
use App\Services\ActiveProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

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
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

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
                'kind'            => 'treatment',
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

        // Scheduled calendar events for this day (excluding archived treatments)
        $calendarEvents = CalendarEvent::with('treatment')
            ->whereDate('scheduled_date', $dateStr)
            ->where('is_cancelled', false)
            ->whereHas('treatment', fn($q) => $q->whereNull('archived_at'))
            ->get();

        foreach ($calendarEvents as $event) {
            $events[] = [
                'kind'            => 'treatment',
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

        $personalEvents = $this->personalEventsAvailable()
            ? PersonalEvent::whereDate('start_date', '<=', $dateStr)
                ->whereDate('end_date', '>=', $dateStr)
                ->orderBy('start_date')
                ->get()
            : collect();

        foreach ($personalEvents as $event) {
            $events[] = [
                'kind'             => 'personal',
                'type'             => 'personal',
                'id'               => $event->id,
                'title'            => $event->title,
                'name'             => $event->title,
                'display_name'     => $event->title,
                'category'         => $event->category,
                'icon'             => $event->icon,
                'color'            => $event->color,
                'notes'            => $event->notes,
                'start_date'       => $event->start_date->toDateString(),
                'end_date'         => $event->end_date->toDateString(),
                'is_multi_day'     => ! $event->start_date->isSameDay($event->end_date),
                'requires_fasting' => false,
                'can_move'         => false,
                'moved'            => false,
            ];
        }

        return $events;
    }

    public function getEventsForMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $grouped = CalendarEvent::with('treatment')
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->where('is_cancelled', false)
            ->whereHas('treatment', fn($q) => $q->whereNull('archived_at'))
            ->get()
            ->groupBy(fn($e) => $e->scheduled_date->toDateString());

        $result = $grouped->map(fn($dayEvents) => $dayEvents->map(fn($e) => [
            'kind'             => 'treatment',
            'treatment_id'     => $e->treatment_id,
            'name'             => $e->treatment->name,
            'display_name'     => $e->treatment->displayName(),
            'color'            => $e->treatment->color,
            'requires_fasting' => $e->treatment->requiresFasting(),
        ])->values()->toArray())->toArray();

        $monthlyPersonalEvents = $this->personalEventsAvailable()
            ? PersonalEvent::forMonth($year, $month)->get()
            : collect();

        foreach ($monthlyPersonalEvents as $event) {
            $cursor = $event->start_date->copy()->max($start);
            $last   = $event->end_date->copy()->min($end);
            for (; $cursor->lte($last); $cursor->addDay()) {
                $key = $cursor->toDateString();
                $result[$key][] = [
                    'kind'             => 'personal',
                    'name'             => $event->title,
                    'display_name'     => $event->title,
                    'color'            => $event->color,
                    'requires_fasting' => false,
                ];
            }
        }

        return $result;
    }

    /**
     * Cherche le prochain jour (dans une fenêtre bornée) contenant au moins un
     * événement. Utile pour l'empty state du Dashboard.
     *
     * @return array{date: \Carbon\Carbon, event: array}|null
     */
    public function getNextEventAfter(Carbon $from, int $maxDays = 60): ?array
    {
        for ($i = 1; $i <= $maxDays; $i++) {
            $day = $from->copy()->addDays($i);
            $events = $this->getEventsForDay($day);
            if (!empty($events)) {
                return ['date' => $day, 'event' => $events[0]];
            }
        }
        return null;
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    /**
     * Les événements personnels sont une fonctionnalité optionnelle : sur un
     * appareil dont le build précède la migration, la table peut être absente.
     * On dégrade alors proprement (calendrier des traitements seuls) au lieu de
     * planter tout l'écran d'accueil.
     */
    private function personalEventsAvailable(): bool
    {
        return Schema::hasTable('personal_events');
    }

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

        if ($treatment->hasIntervalDose()) {
            $dose   = $history ? (float) $history->dose : (float) $treatment->current_dose;
            $times  = $history?->times_per_day ?? $treatment->times_per_day;
            return $this->formatInterval($treatment, $dose, (int) $times);
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

        if ($treatment->hasIntervalDose()) {
            return $this->formatInterval($treatment, (float) $treatment->current_dose, (int) $treatment->times_per_day);
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

    private function formatInterval(Treatment $t, float $dose, int $times): string
    {
        $doseStr = $this->formatAmount($dose, $t->unit);
        $hours   = $times > 0 ? (int) round(24 / $times) : 0;
        return "{$doseStr} · {$times}×/jour · toutes les {$hours}h";
    }

    private function formatAmount(float $amount, ?string $unit): string
    {
        if ($unit === 'ml') {
            $formatted = number_format($amount, 1, ',', '');
        } elseif ($amount == (int) $amount) {
            $formatted = (string)(int)$amount;
        } else {
            $rounded = round($amount * 2) / 2;
            $formatted = $rounded == (int)$rounded
                ? (string)(int)$rounded
                : number_format($rounded, 1, ',', '');
        }
        return $unit ? "{$formatted} {$unit}" : $formatted;
    }
}
