<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Treatment;
use Carbon\Carbon;
use Ikromjon\LocalNotifications\Enums\RepeatInterval;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;
use App\Support\Platform;

class NotificationScheduler
{
    /** null = not yet checked, false/true = result of Battery.CheckStatus */
    private ?bool $batteryUnrestricted = null;

    public function rescheduleAll(): void
    {
        LocalNotifications::cancelAll();

        $this->batteryUnrestricted = $this->checkBatteryUnrestricted();

        Treatment::active()
            ->where(fn ($q) => $q->where('notification_enabled', true)->orWhere('order_reminder_enabled', true))
            ->get()
            ->each(fn (Treatment $t) => $this->scheduleForTreatment($t));
    }

    public function scheduleForTreatment(Treatment $treatment): void
    {
        $this->cancelForTreatment($treatment);

        if (! $treatment->notification_enabled && ! $treatment->order_reminder_enabled) {
            return;
        }

        // Initialize battery flag if called directly (not via rescheduleAll).
        if ($this->batteryUnrestricted === null) {
            $this->batteryUnrestricted = $this->checkBatteryUnrestricted();
        }

        if ($treatment->order_reminder_enabled) {
            $this->scheduleOrderReminders($treatment);
        }

        if (! $treatment->notification_enabled) {
            return;
        }

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
            LocalNotifications::cancel($prefix . '-order-' . $eventId);
        }

        // Rappels de commande d'un traitement lié : identifiés par l'occurrence parente.
        if ($treatment->parent_treatment_id !== null) {
            CalendarEvent::where('treatment_id', $treatment->id)
                ->where('scheduled_date', '>=', today()->toDateString())
                ->whereNotNull('parent_event_id')
                ->distinct()
                ->pluck('parent_event_id')
                ->each(fn ($id) => LocalNotifications::cancel($prefix . '-order-' . $id));
        }
    }

    /**
     * Reprogramme un traitement et ses traitements liés (dont les dates
     * suivent celles du parent) après un déplacement / saut / recalcul.
     */
    public function rescheduleWithLinked(Treatment $treatment): void
    {
        $this->scheduleForTreatment($treatment);

        Treatment::active()
            ->where('parent_treatment_id', $treatment->id)
            ->get()
            ->each(fn (Treatment $child) => $this->scheduleForTreatment($child));
    }

    /**
     * Dates de début des prochains blocs de prise, indexées par un identifiant
     * stable de bloc (id de l'occurrence parente pour un traitement lié, id de
     * l'occurrence sinon). Un traitement quotidien autonome n'a pas de bloc.
     *
     * @return array<int, Carbon>
     */
    public function upcomingBlockStarts(Treatment $treatment): array
    {
        $today  = today()->toDateString();
        $cutoff = today()->addYear()->toDateString();

        $events = CalendarEvent::where('treatment_id', $treatment->id)
            ->where('is_cancelled', false)
            ->where('scheduled_date', '<=', $cutoff)
            ->orderBy('scheduled_date')
            ->get(['id', 'scheduled_date', 'parent_event_id']);

        $starts = [];

        if ($treatment->parent_treatment_id !== null) {
            foreach ($events->whereNotNull('parent_event_id')->groupBy('parent_event_id') as $parentId => $block) {
                $first = $block->first()->scheduled_date;
                // Un bloc déjà commencé n'a plus besoin de rappel de commande.
                if ($first->toDateString() >= $today) {
                    $starts[(int) $parentId] = $first->copy();
                }
            }
            return $starts;
        }

        if ($treatment->type === 'daily') {
            return [];
        }

        foreach ($events as $event) {
            if ($event->scheduled_date->toDateString() >= $today) {
                $starts[$event->id] = $event->scheduled_date->copy();
            }
        }
        return $starts;
    }

    private function scheduleOrderReminders(Treatment $treatment): void
    {
        $prefix = 'treatment-' . $treatment->id;
        $days   = max(0, (int) ($treatment->order_reminder_days_before ?? 3));
        [$h, $m] = explode(':', $treatment->order_reminder_time ?: '09:00');

        foreach ($this->upcomingBlockStarts($treatment) as $blockId => $start) {
            $at = $start->copy()->subDays($days)->setTime((int) $h, (int) $m);
            if ($at->isPast()) {
                continue;
            }

            $this->push([
                'id'   => $prefix . '-order-' . $blockId,
                'body' => __('treatments.order_reminder_body', [
                    'name' => $treatment->displayName(),
                    'date' => $start->locale(app()->getLocale())->isoFormat('dddd D MMMM'),
                ]),
                'at'   => $at->timestamp,
            ], $treatment);
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

        // iOS n'a pas d'optimisation batterie qui tue les alarmes : snooze toujours fiable
        if (Platform::isIos()) {
            return true;
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
