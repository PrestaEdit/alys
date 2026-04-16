<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Treatment;
use Carbon\Carbon;

class EventMoveService
{
    public function move(CalendarEvent $event, string $newDate): void
    {
        $previousDate = $event->scheduled_date->toDateString();

        if ($event->original_date === null) {
            $event->original_date = $previousDate;
        }

        $event->scheduled_date = $newDate;
        $event->save();

        if ($event->treatment->name === 'IT MTTX') {
            $this->applyMtxCoherenceRule($previousDate, $newDate);
        }
    }

    private function applyMtxCoherenceRule(string $previousDate, string $newDate): void
    {
        $mtx = Treatment::where('name', 'MTX')->first();
        if (!$mtx) return;

        $previousCarbon = Carbon::parse($previousDate);
        $newCarbon = Carbon::parse($newDate);

        if ($previousCarbon->dayOfWeek === Carbon::TUESDAY) {
            CalendarEvent::where('treatment_id', $mtx->id)
                ->whereDate('scheduled_date', $previousDate)
                ->update(['is_cancelled' => false]);
        }

        if ($newCarbon->dayOfWeek === Carbon::TUESDAY) {
            CalendarEvent::where('treatment_id', $mtx->id)
                ->whereDate('scheduled_date', $newDate)
                ->update(['is_cancelled' => true]);
        }
    }
}
