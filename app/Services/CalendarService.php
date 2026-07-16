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
            'icon'         => $t->widget_icon ?? 'pill',
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

        // "Porteur" du bloc = jour à partir duquel on peut décaler le bloc.
        // Priorité au premier jour restant (≥ aujourd'hui) pour qu'un bloc dont
        // le début est passé reste manipulable ; fallback sur le min global si
        // tout le bloc est passé.
        $today = Carbon::today()->toDateString();
        $parentEventIds = $calendarEvents->pluck('parent_event_id')->filter()->unique()->values();
        $firstDateByParent = $parentEventIds->isEmpty()
            ? collect()
            : CalendarEvent::whereIn('parent_event_id', $parentEventIds)
                ->where('is_cancelled', false)
                ->selectRaw('parent_event_id, MIN(scheduled_date) as first_overall, MIN(CASE WHEN scheduled_date >= ? THEN scheduled_date END) as first_future', [$today])
                ->groupBy('parent_event_id')
                ->get()
                ->mapWithKeys(fn($row) => [
                    $row->parent_event_id => $row->first_future ?? $row->first_overall,
                ]);

        foreach ($calendarEvents as $event) {
            $canMove = $event->parent_event_id === null
                || $firstDateByParent->get($event->parent_event_id) === $dateStr;

            $dayparts = $this->buildDaypartsForEvent($event);

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
                'dose_parts'      => $dayparts,
                'has_dayparts'    => $dayparts !== null,
                'color'           => $event->treatment->color,
                'requires_fasting' => $event->treatment->requiresFasting(),
                'can_move'        => $canMove,
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
     * Coût : jusqu'à $maxDays × 3 requêtes (une par source dans getEventsForDay).
     * OK pour un empty state ponctuel — ne pas boucler cette méthode en masse.
     *
     * @return array{date: \Carbon\Carbon, event: array}|null
     */
    public function getNextEventAfter(Carbon $from, int $maxDays = 60): ?array
    {
        if ($maxDays < 1) return null;

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

    /**
     * Structured dayparts for a calendar event — used by the view to render each
     * dose separately and mark skipped ones visually. Returns null if the
     * treatment does not use dayparts.
     *
     * @return list<array{daypart: string, text: string, skipped: bool}>|null
     */
    private function buildDaypartsForEvent(CalendarEvent $event): ?array
    {
        $t = $event->treatment;
        if ($t->is_medical_act || ! $t->hasDayPartDoses()) {
            return null;
        }

        $parts = [];
        if ($t->dose_morning !== null) {
            $parts[] = [
                'daypart' => 'morning',
                'text'    => $this->formatAmount((float) $t->dose_morning, $t->unit) . ' matin',
                'skipped' => (bool) $event->skip_morning,
            ];
        }
        if ($t->dose_noon !== null) {
            $parts[] = [
                'daypart' => 'noon',
                'text'    => $this->formatAmount((float) $t->dose_noon, $t->unit) . ' midi',
                'skipped' => (bool) $event->skip_noon,
            ];
        }
        if ($t->dose_evening !== null) {
            $parts[] = [
                'daypart' => 'evening',
                'text'    => $this->formatAmount((float) $t->dose_evening, $t->unit) . ' soir',
                'skipped' => (bool) $event->skip_evening,
            ];
        }
        return $parts;
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
