<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Setting;
use App\Models\Treatment;
use Carbon\Carbon;

class CalendarService
{
    public function getDaysRemaining(Carbon $from): int
    {
        $end = Carbon::parse(Setting::get('treatment_end', '2027-03-31'));
        return (int) $from->copy()->startOfDay()->diffInDays($end->copy()->startOfDay(), false);
    }

    public function getCounters(Carbon $from): array
    {
        $fromDate = $from->toDateString();

        $treatments = Treatment::whereIn('name', ['Hôpital', 'VCR', 'IT MTTX', 'MTX'])
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

    public function getNextHospitalVisit(Carbon $from): ?Carbon
    {
        $hopital = Treatment::where('name', 'Hôpital')->first();
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
        $dailyTreatments = Treatment::where('type', 'daily')->with('posologyHistory')->get();
        foreach ($dailyTreatments as $treatment) {
            $events[] = [
                'id' => null,
                'treatment_id' => $treatment->id,
                'name' => $treatment->name,
                'commercial_name' => $treatment->commercial_name,
                'type' => 'daily',
                'unit' => $treatment->unit,
                'dose' => $this->getDoseForDate($treatment, $date),
                'color' => $treatment->color,
                'requires_fasting' => false,
                'can_move' => false,
                'moved' => false,
            ];
        }

        // Scheduled calendar events for this day
        $calendarEvents = CalendarEvent::with('treatment')
            ->whereDate('scheduled_date', $dateStr)
            ->where('is_cancelled', false)
            ->get();

        foreach ($calendarEvents as $event) {
            $events[] = [
                'id' => $event->id,
                'treatment_id' => $event->treatment_id,
                'name' => $event->treatment->name,
                'commercial_name' => $event->treatment->commercial_name,
                'type' => $event->treatment->type,
                'unit' => $event->treatment->unit,
                'dose' => $event->treatment->current_dose,
                'color' => $event->treatment->color,
                'requires_fasting' => $event->treatment->requiresFasting(),
                'can_move' => true,
                'moved' => $event->hasMoved(),
                'original_date' => $event->original_date?->toDateString(),
                'notes' => $event->notes,
            ];
        }

        return $events;
    }

    public function getEventsForMonth(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        // Daily treatments (6-MP, 6-TG) are not shown on the monthly grid — only scheduled events get dots.
        $events = CalendarEvent::with('treatment')
            ->whereBetween('scheduled_date', [$start->toDateString(), $end->toDateString()])
            ->where('is_cancelled', false)
            ->get()
            ->groupBy(fn($e) => $e->scheduled_date->toDateString());

        return $events->map(fn($dayEvents) => $dayEvents->map(fn($e) => [
            'treatment_id' => $e->treatment_id,
            'name' => $e->treatment->name,
            'color' => $e->treatment->color,
            'requires_fasting' => $e->treatment->requiresFasting(),
        ])->values()->toArray())->toArray();
    }

    private function getDoseForDate(Treatment $treatment, Carbon $date): ?string
    {
        $history = $treatment->posologyHistory
            ->filter(fn($h) => $h->started_at->lte($date))
            ->first();

        $dose = $history ? $history->dose : $treatment->current_dose;

        return $dose !== null ? "{$dose} {$treatment->unit}" : null;
    }
}
