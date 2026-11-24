<?php

namespace App\Services;

use App\Models\CalendarEvent;
use App\Models\Setting;
use App\Models\Treatment;
use Carbon\Carbon;
use Ikromjon\LocalNotifications\Enums\RepeatInterval;
use Ikromjon\LocalNotifications\Facades\LocalNotifications;
use App\Support\Platform;

class NotificationScheduler
{
    /**
     * Pendant une pause datée, les rappels répétés sont remplacés par des
     * rappels ponctuels (iOS ne sait pas différer le départ d'un rappel
     * répété). On en programme jusqu'à N jours après la reprise ; l'ouverture
     * de l'app (refreshPausedTreatments) prolonge puis rebascule en répété.
     */
    private const PAUSE_DAYS_AFTER_RESUME = 14;
    private const PAUSE_REFRESH_MARGIN_DAYS = 7;

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

        if ($this->hasRepeatingReminders($treatment) && $this->pauseAffects($treatment)) {
            $this->schedulePausedRepeating($treatment);
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

        $this->cancelPausedRepeating($treatment);

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
            ->notPaused()
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

    /**
     * À appeler à l'ouverture de l'app : prolonge les rappels ponctuels des
     * traitements en pause, et rebascule en rappels répétés une fois la
     * pause terminée.
     */
    public function refreshPausedTreatments(): void
    {
        $limit = today()->addDays(self::PAUSE_REFRESH_MARGIN_DAYS)->toDateString();

        Setting::where('key', 'like', 'pause_notif_%')->get()->each(function (Setting $marker) use ($limit) {
            $data = json_decode($marker->value, true) ?: [];
            $treatment = Treatment::withoutGlobalScopes()->find((int) substr($marker->key, strlen('pause_notif_')));

            if (! $treatment || $treatment->archived_at !== null) {
                $this->cancelPausedRepeatingFromMarker($marker);
                return;
            }

            $stale = ! $this->pauseAffects($treatment)
                || ($data['through'] ?? '') <= $limit;

            if ($stale) {
                $this->scheduleForTreatment($treatment);
            }
        });
    }

    /** Le traitement a-t-il des rappels répétés (quotidiens / hebdo simples) ? */
    private function hasRepeatingReminders(Treatment $treatment): bool
    {
        return $treatment->type === 'daily'
            || ($treatment->type === 'weekly' && $treatment->frequency_weeks === 1);
    }

    /** Une pause en cours ou à venir (propre ou du parent) concerne ce traitement. */
    private function pauseAffects(Treatment $treatment): bool
    {
        return $treatment->hasPendingPause()
            || ($treatment->parent_treatment_id !== null && (bool) $treatment->parentTreatment?->hasPendingPause());
    }

    /** Début de la pause en cours ou à venir (propre ou du parent), la plus proche. */
    private function pauseStart(Treatment $treatment): Carbon
    {
        $parent = $treatment->parent_treatment_id !== null ? $treatment->parentTreatment : null;

        return collect([$treatment, $parent])
            ->filter(fn (?Treatment $t) => $t?->hasPendingPause())
            ->map(fn (Treatment $t) => $t->paused_from->copy())
            ->sort()
            ->first() ?? today();
    }

    /**
     * Créneaux des rappels répétés : [clé, heure de départ, moment, décalage en secondes].
     *
     * @return array<int, array{0: string, 1: string, 2: string, 3: int}>
     */
    private function repeatingSlots(Treatment $treatment): array
    {
        if ($treatment->type === 'weekly' || $treatment->is_medical_act
            || (! $treatment->hasDayPartDoses() && ! $treatment->hasIntervalDose())) {
            return $treatment->notification_time_morning
                ? [['morning', $treatment->notification_time_morning, 'morning', 0]]
                : [];
        }

        if ($treatment->hasIntervalDose()) {
            if (! $treatment->notification_time_morning || $treatment->times_per_day <= 0) return [];
            $step = (int) (86400 / $treatment->times_per_day);
            $slots = [];
            for ($i = 0; $i < $treatment->times_per_day; $i++) {
                $slots[] = ['i' . $i, $treatment->notification_time_morning, 'interval', $i * $step];
            }
            return $slots;
        }

        $slots = [];
        if ($treatment->notification_time_morning) {
            $slots[] = ['morning', $treatment->notification_time_morning, 'morning', 0];
        }
        if ($treatment->notification_time_noon && $treatment->dose_noon !== null) {
            $slots[] = ['noon', $treatment->notification_time_noon, 'noon', 0];
        }
        if ($treatment->notification_time_evening && $treatment->dose_evening !== null) {
            $slots[] = ['evening', $treatment->notification_time_evening, 'evening', 0];
        }
        return $slots;
    }

    private function schedulePausedRepeating(Treatment $treatment): void
    {
        $slots = $this->repeatingSlots($treatment);
        if ($slots === []) return;

        $today = today();
        // Sans date de reprise : rien à programmer après le début de la pause.
        $resume = $treatment->resumeDateFrom($this->pauseStart($treatment)->max($today->copy()));
        // Carbon::max() renvoie l'une des deux instances : on copie avant de muter.
        $through = ($resume !== null && $resume->gt($today) ? $resume->copy() : $today->copy())
            ->addDays(self::PAUSE_DAYS_AFTER_RESUME);

        $prefix = 'treatment-' . $treatment->id . '-pause-';
        // day_of_week : 0=lundi…6=dimanche ; Carbon dayOfWeekIso : 1=lundi…7=dimanche
        $weekday = $treatment->type === 'weekly' ? (($treatment->day_of_week ?? 0) + 1) : null;

        for ($day = $today->copy(); $day->lte($through); $day->addDay()) {
            if ($treatment->isPausedOn($day)) continue;
            if ($weekday !== null && $day->dayOfWeekIso !== $weekday) continue;

            foreach ($slots as [$key, $time, $moment, $offset]) {
                [$h, $m] = explode(':', $time);
                $at = $day->copy()->setTime((int) $h, (int) $m)->addSeconds($offset);
                if ($at->isPast()) continue;

                $this->push([
                    'id'   => $prefix . $key . '-' . $day->format('Ymd'),
                    'body' => $this->buildBody($treatment, $moment),
                    'at'   => $at->timestamp,
                ], $treatment);
            }
        }

        Setting::set('pause_notif_' . $treatment->id, json_encode([
            'from'    => $today->toDateString(),
            'through' => $through->toDateString(),
            'slots'   => array_column($slots, 0),
        ]));
    }

    private function cancelPausedRepeating(Treatment $treatment): void
    {
        $marker = Setting::find('pause_notif_' . $treatment->id);
        if ($marker) {
            $this->cancelPausedRepeatingFromMarker($marker);
        }
    }

    private function cancelPausedRepeatingFromMarker(Setting $marker): void
    {
        $data   = json_decode($marker->value, true) ?: [];
        $prefix = 'treatment-' . substr($marker->key, strlen('pause_notif_')) . '-pause-';

        if (isset($data['from'], $data['through'])) {
            $from = Carbon::parse($data['from']);
            if ($from->lt(today())) $from = today();
            for ($day = $from; $day->toDateString() <= $data['through']; $day->addDay()) {
                foreach ($data['slots'] ?? [] as $key) {
                    LocalNotifications::cancel($prefix . $key . '-' . $day->format('Ymd'));
                }
            }
        }

        $marker->delete();
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
            ->notPaused()
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
