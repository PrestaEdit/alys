<?php

namespace App\Services;

use Carbon\Carbon;

class WidgetService
{
    public function __construct(private CalendarService $calendar) {}

    public function refresh(): void
    {
        if (!function_exists('nativephp_call')) return;

        $today = Carbon::today();

        $daysRemaining = $this->calendar->getDaysRemaining($today);
        $progress = $this->calendar->getProgressPercent($today) ?? 0;

        if ($daysRemaining !== null) {
            nativephp_call('Widget.UpdateDaysRemaining', json_encode([
                'days_remaining' => $daysRemaining,
                'progress' => $progress,
            ]));
        }

        $events = $this->calendar->getEventsForDay($today);
        $eventNames = array_values(array_map(fn($e) => $e['display_name'], $events));

        nativephp_call('Widget.UpdateCalendar', json_encode([
            'date' => $today->locale('fr')->isoFormat('dddd D MMMM'),
            'events' => $eventNames,
        ]));
    }
}
