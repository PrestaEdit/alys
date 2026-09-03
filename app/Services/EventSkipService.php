<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Treatment;
use Carbon\Carbon;

class EventSkipService
{
    public function skip(CalendarEvent $event): void
    {
        $event->is_cancelled = true;
        $event->save();

        if ($event->treatment->name === 'IT MTTX') {
            $this->restoreMtxOnDate($event->scheduled_date->toDateString());
        }

        if ($event->treatment->name === 'VCR') {
            $this->skipChildEvents($event);
        }
    }

    public function restore(CalendarEvent $event): void
    {
        $event->is_cancelled = false;
        $event->save();

        if ($event->treatment->name === 'IT MTTX') {
            $this->cancelMtxOnDate($event->scheduled_date->toDateString());
        }

        if ($event->treatment->name === 'VCR') {
            $this->restoreChildEvents($event);
        }
    }

    private function skipChildEvents(CalendarEvent $parent): void
    {
        CalendarEvent::where('parent_event_id', $parent->id)
            ->update(['is_cancelled' => true]);
    }

    private function restoreChildEvents(CalendarEvent $parent): void
    {
        CalendarEvent::where('parent_event_id', $parent->id)
            ->update(['is_cancelled' => false]);
    }

    private function restoreMtxOnDate(string $date): void
    {
        if (Carbon::parse($date)->dayOfWeek !== Carbon::TUESDAY) return;

        $mtx = Treatment::where('name', 'MTX')->first();
        if (!$mtx) return;

        CalendarEvent::where('treatment_id', $mtx->id)
            ->whereDate('scheduled_date', $date)
            ->update(['is_cancelled' => false]);
    }

    private function cancelMtxOnDate(string $date): void
    {
        if (Carbon::parse($date)->dayOfWeek !== Carbon::TUESDAY) return;

        $mtx = Treatment::where('name', 'MTX')->first();
        if (!$mtx) return;

        CalendarEvent::where('treatment_id', $mtx->id)
            ->whereDate('scheduled_date', $date)
            ->update(['is_cancelled' => true]);
    }
}
