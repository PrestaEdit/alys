<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Treatment;
use Carbon\Carbon;
use Ikromjon\LocalNotifications\Enums\RepeatInterval;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;

class NotificationScheduler
{
    public function rescheduleAll(): void
    {
        Treatment::active()
            ->where('notification_enabled', true)
            ->get()
            ->each(fn (Treatment $t) => $this->scheduleForTreatment($t));
    }

    public function scheduleForTreatment(Treatment $treatment): void
    {
        if (! $treatment->notification_enabled) {
            return;
        }

        $this->cancelForTreatment($treatment);

        match ($treatment->type) {
            'daily'  => $this->scheduleDaily($treatment),
            'weekly' => $this->scheduleWeekly($treatment),
            'cyclic' => $this->scheduleCyclic($treatment),
            default  => null,
        };
    }

    public function cancelForTreatment(Treatment $treatment): void
    {
        $prefix = 'treatment-' . $treatment->id;

        LocalNotifications::cancel($prefix . '-morning');
        LocalNotifications::cancel($prefix . '-noon');
        LocalNotifications::cancel($prefix . '-evening');
        LocalNotifications::cancel($prefix . '-interval');

        $futureEvents = CalendarEvent::where('treatment_id', $treatment->id)
            ->where('scheduled_date', '>=', today()->toDateString())
            ->pluck('id');

        foreach ($futureEvents as $eventId) {
            LocalNotifications::cancel($prefix . '-event-' . $eventId);
        }
    }

    private function scheduleDaily(Treatment $treatment): void
    {
        $prefix = 'treatment-' . $treatment->id;
        $title  = $treatment->displayName();

        if ($treatment->is_medical_act || (! $treatment->hasDayPartDoses() && ! $treatment->hasIntervalDose())) {
            if ($treatment->notification_time_morning) {
                LocalNotifications::schedule([
                    'id'     => $prefix . '-morning',
                    'title'  => $title,
                    'body'   => $this->buildBody($treatment, 'morning'),
                    'at'     => $this->todayAt($treatment->notification_time_morning),
                    'repeat' => RepeatInterval::Daily,
                ]);
            }
            return;
        }

        if ($treatment->hasIntervalDose()) {
            if ($treatment->notification_time_morning && $treatment->times_per_day > 0) {
                $intervalSeconds = (int) (86400 / $treatment->times_per_day);
                LocalNotifications::schedule([
                    'id'                    => $prefix . '-interval',
                    'title'                 => $title,
                    'body'                  => $this->buildBody($treatment, 'interval'),
                    'at'                    => $this->todayAt($treatment->notification_time_morning),
                    'repeatIntervalSeconds' => $intervalSeconds,
                ]);
            }
            return;
        }

        // Dayparts mode
        if ($treatment->notification_time_morning) {
            LocalNotifications::schedule([
                'id'     => $prefix . '-morning',
                'title'  => $title,
                'body'   => $this->buildBody($treatment, 'morning'),
                'at'     => $this->todayAt($treatment->notification_time_morning),
                'repeat' => RepeatInterval::Daily,
            ]);
        }
        if ($treatment->notification_time_noon && $treatment->dose_noon !== null) {
            LocalNotifications::schedule([
                'id'     => $prefix . '-noon',
                'title'  => $title,
                'body'   => $this->buildBody($treatment, 'noon'),
                'at'     => $this->todayAt($treatment->notification_time_noon),
                'repeat' => RepeatInterval::Daily,
            ]);
        }
        if ($treatment->notification_time_evening && $treatment->dose_evening !== null) {
            LocalNotifications::schedule([
                'id'     => $prefix . '-evening',
                'title'  => $title,
                'body'   => $this->buildBody($treatment, 'evening'),
                'at'     => $this->todayAt($treatment->notification_time_evening),
                'repeat' => RepeatInterval::Daily,
            ]);
        }
    }

    private function scheduleWeekly(Treatment $treatment): void
    {
        if (! $treatment->notification_time_morning) {
            return;
        }

        $prefix = 'treatment-' . $treatment->id;
        $title  = $treatment->displayName();

        if ($treatment->frequency_weeks === 1) {
            // day_of_week: 0=lundi…6=dimanche → repeatDays: 1=lun…7=dim
            $repeatDay = ($treatment->day_of_week ?? 0) + 1;

            LocalNotifications::schedule([
                'id'         => $prefix . '-morning',
                'title'      => $title,
                'body'       => $this->buildBody($treatment, 'morning'),
                'at'         => $this->todayAt($treatment->notification_time_morning),
                'repeatDays' => [$repeatDay],
            ]);
            return;
        }

        $this->scheduleOneShots($treatment);
    }

    private function scheduleCyclic(Treatment $treatment): void
    {
        if (! $treatment->notification_time_morning) {
            return;
        }

        $this->scheduleOneShots($treatment);
    }

    private function scheduleOneShots(Treatment $treatment): void
    {
        $prefix = 'treatment-' . $treatment->id;
        $title  = $treatment->displayName();
        $cutoff = today()->addYear()->toDateString();

        $events = CalendarEvent::where('treatment_id', $treatment->id)
            ->where('scheduled_date', '>=', today()->toDateString())
            ->where('scheduled_date', '<=', $cutoff)
            ->where('is_cancelled', false)
            ->get(['id', 'scheduled_date']);

        [$h, $m] = explode(':', $treatment->notification_time_morning);

        foreach ($events as $event) {
            $at = Carbon::parse($event->scheduled_date)->setTime((int) $h, (int) $m)->timestamp;

            LocalNotifications::schedule([
                'id'    => $prefix . '-event-' . $event->id,
                'title' => $title,
                'body'  => $this->buildBody($treatment, 'morning'),
                'at'    => $at,
            ]);
        }
    }

    private function todayAt(string $time): int
    {
        [$h, $m] = explode(':', $time);
        $dt = Carbon::today()->setTime((int) $h, (int) $m);

        if ($dt->isPast()) {
            $dt->addDay();
        }

        return $dt->timestamp;
    }

    private function buildBody(Treatment $treatment, string $moment): string
    {
        $name = $treatment->displayName();

        if ($treatment->is_medical_act) {
            $base = "Rappel : {$name}";
            return $treatment->requires_fasting ? "{$base} — à jeun !" : $base;
        }

        $unit = $treatment->unit ?? '';

        $dose = match ($moment) {
            'morning'  => $treatment->dose_morning ?? $treatment->current_dose,
            'noon'     => $treatment->dose_noon,
            'evening'  => $treatment->dose_evening,
            'interval' => $treatment->current_dose,
            default    => $treatment->current_dose,
        };

        $doseStr = $dose !== null
            ? number_format((float) $dose, 2, ',', '') . ($unit ? " {$unit}" : '')
            : '';

        $base = $doseStr
            ? "Prenez votre {$name} ({$doseStr})"
            : "Prenez votre {$name}";

        return $treatment->requires_fasting ? "{$base} — à jeun !" : $base;
    }
}
