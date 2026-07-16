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

        if ($event->parent_event_id !== null) {
            // Enfant : décale les jours suivants du bloc, ne touche pas au parent.
            $this->shiftSiblingEvents($event, $previousDate, $newDate);
            return;
        }

        if ($event->treatment->name === 'IT MTTX') {
            $this->applyMtxCoherenceRule($previousDate, $newDate);
        }

        $this->shiftChildEvents($event, $previousDate, $newDate);
    }

    private function shiftChildEvents(CalendarEvent $parent, string $previousDate, string $newDate): void
    {
        $delta = Carbon::parse($previousDate)->diffInDays(Carbon::parse($newDate), false);

        CalendarEvent::where('parent_event_id', $parent->id)->get()
            ->each(function (CalendarEvent $child) use ($delta) {
                if ($child->original_date === null) {
                    $child->original_date = $child->scheduled_date->toDateString();
                }
                $child->scheduled_date = $child->scheduled_date->copy()->addDays($delta)->toDateString();
                $child->save();
            });
    }

    private function shiftSiblingEvents(CalendarEvent $moved, string $previousDate, string $newDate): void
    {
        $delta = Carbon::parse($previousDate)->diffInDays(Carbon::parse($newDate), false);

        CalendarEvent::where('parent_event_id', $moved->parent_event_id)
            ->where('id', '!=', $moved->id)
            ->get()
            ->each(function (CalendarEvent $sibling) use ($delta) {
                if ($sibling->original_date === null) {
                    $sibling->original_date = $sibling->scheduled_date->toDateString();
                }
                $sibling->scheduled_date = $sibling->scheduled_date->copy()->addDays($delta)->toDateString();
                $sibling->save();
            });
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
