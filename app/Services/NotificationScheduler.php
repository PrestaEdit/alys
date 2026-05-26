<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Treatment;
use Carbon\Carbon;
use Ikromjon\LocalNotifications\Enums\RepeatInterval;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;

class NotificationScheduler
{
    /** null = not yet checked, false/true = result of Battery.CheckStatus */
    private ?bool $batteryUnrestricted = null;

    public function rescheduleAll(): void
    {
        $this->batteryUnrestricted = $this->checkBatteryUnrestricted();

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

        // Initialize battery flag if called directly (not via rescheduleAll).
        if ($this->batteryUnrestricted === null) {
            $this->batteryUnrestricted = $this->checkBatteryUnrestricted();
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

        if ($treatment->is_medical_act || (! $treatment->hasDayPartDoses() && ! $treatment->hasIntervalDose())) {
            if ($treatment->notification_time_morning) {
                $this->push([
                    'id'     => $prefix . '-morning',
                    'body'   => $this->buildBody($treatment, 'morning'),
                    'at'     => $this->todayAt($treatment->notification_time_morning),
                    'repeat' => RepeatInterval::Daily,
                ], $treatment);
            }
            return;
        }

        if ($treatment->hasIntervalDose()) {
            if ($treatment->notification_time_morning && $treatment->times_per_day > 0) {
                $intervalSeconds = (int) (86400 / $treatment->times_per_day);
                $this->push([
                    'id'                    => $prefix . '-interval',
                    'body'                  => $this->buildBody($treatment, 'interval'),
                    'at'                    => $this->todayAt($treatment->notification_time_morning),
                    'repeatIntervalSeconds' => $intervalSeconds,
                ], $treatment);
            }
            return;
        }

        // Dayparts mode
        if ($treatment->notification_time_morning) {
            $this->push([
                'id'     => $prefix . '-morning',
                'body'   => $this->buildBody($treatment, 'morning'),
                'at'     => $this->todayAt($treatment->notification_time_morning),
                'repeat' => RepeatInterval::Daily,
            ], $treatment);
        }
        if ($treatment->notification_time_noon && $treatment->dose_noon !== null) {
            $this->push([
                'id'     => $prefix . '-noon',
                'body'   => $this->buildBody($treatment, 'noon'),
                'at'     => $this->todayAt($treatment->notification_time_noon),
                'repeat' => RepeatInterval::Daily,
            ], $treatment);
        }
        if ($treatment->notification_time_evening && $treatment->dose_evening !== null) {
            $this->push([
                'id'     => $prefix . '-evening',
                'body'   => $this->buildBody($treatment, 'evening'),
                'at'     => $this->todayAt($treatment->notification_time_evening),
                'repeat' => RepeatInterval::Daily,
            ], $treatment);
        }
    }

    private function scheduleWeekly(Treatment $treatment): void
    {
        if (! $treatment->notification_time_morning) {
            return;
        }

        $prefix = 'treatment-' . $treatment->id;

        if ($treatment->frequency_weeks === 1) {
            // day_of_week: 0=lundi…6=dimanche → repeatDays: 1=lun…7=dim
            $repeatDay = ($treatment->day_of_week ?? 0) + 1;

            $this->push([
                'id'         => $prefix . '-morning',
                'body'       => $this->buildBody($treatment, 'morning'),
                'at'         => $this->todayAt($treatment->notification_time_morning),
                'repeatDays' => [$repeatDay],
            ], $treatment);
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
        $cutoff = today()->addYear()->toDateString();

        $events = CalendarEvent::where('treatment_id', $treatment->id)
            ->where('scheduled_date', '>=', today()->toDateString())
            ->where('scheduled_date', '<=', $cutoff)
            ->where('is_cancelled', false)
            ->get(['id', 'scheduled_date']);

        [$h, $m] = explode(':', $treatment->notification_time_morning);

        foreach ($events as $event) {
            $at = Carbon::parse($event->scheduled_date)->setTime((int) $h, (int) $m)->timestamp;

            $this->push([
                'id'   => $prefix . '-event-' . $event->id,
                'body' => $this->buildBody($treatment, 'morning'),
                'at'   => $at,
            ], $treatment);
        }
    }

    /**
     * Schedule a notification with the standard Alys branding.
     * Snooze actions are only added when battery optimization is unrestricted,
     * since they rely on setExactAndAllowWhileIdle() and would silently fail otherwise.
     */
    private function push(array $params, Treatment $treatment): void
    {
        $actions = ($this->batteryUnrestricted === true)
            ? [
                ['id' => 'snooze5',  'title' => '+ 5 min',  'snooze' => 300],
                ['id' => 'snooze15', 'title' => '+ 15 min', 'snooze' => 900],
                ['id' => 'dismiss',  'title' => 'OK'],
            ]
            : [
                ['id' => 'dismiss', 'title' => 'OK'],
            ];

        LocalNotifications::schedule(array_merge([
            'title'    => 'Alys',
            'subtitle' => $treatment->displayName(),
            'image'    => 'https://prestaedit.app/alys/icon.svg',
            'actions'  => $actions,
        ], $params));
    }

    private function checkBatteryUnrestricted(): bool
    {
        if (! function_exists('nativephp_call')) {
            return false;
        }

        $result = nativephp_call('Battery.CheckStatus', '{}');

        return ($result['unrestricted'] ?? false) === true;
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
