<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    use BelongsToActiveProfile;

    protected $fillable = [
        'profile_id',
        'name', 'commercial_name', 'type', 'unit', 'current_dose',
        'dose_morning', 'dose_noon', 'dose_evening', 'times_per_day',
        'color', 'frequency_weeks', 'is_medical_act', 'requires_fasting',
        'day_of_week', 'recurrence_start', 'notes',
        'show_widget', 'widget_icon',
        'parent_treatment_id', 'linked_days',
        'archived_at',
        'paused_from',
        'paused_until',
        'sort_order',
        'notification_enabled',
        'notification_time_morning',
        'notification_time_noon',
        'notification_time_evening',
        'order_reminder_enabled',
        'order_reminder_days_before',
        'order_reminder_time',
    ];

    protected $casts = [
        'current_dose'  => 'decimal:2',
        'dose_morning'  => 'decimal:2',
        'dose_noon'     => 'decimal:2',
        'dose_evening'  => 'decimal:2',
        'times_per_day'    => 'integer',
        'recurrence_start' => 'date',
        'archived_at'      => 'datetime',
        'paused_from'      => 'date',
        'paused_until'     => 'date',
        'frequency_weeks'  => 'integer',
        'day_of_week'      => 'integer',
        'linked_days'      => 'integer',
        'is_medical_act'        => 'boolean',
        'requires_fasting'      => 'boolean',
        'sort_order'            => 'integer',
        'notification_enabled'  => 'boolean',
        'order_reminder_enabled'     => 'boolean',
        'order_reminder_days_before' => 'integer',
    ];

    public function scopeActive(Builder $query): void
    {
        $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): void
    {
        $query->whereNotNull('archived_at');
    }

    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    /**
     * Met le traitement en pause du $from au $until inclus ($until null =
     * jusqu'à reprise manuelle). Une seule pause est mémorisée : une nouvelle
     * pause remplace la précédente.
     */
    public function pause(Carbon $from, ?Carbon $until = null): void
    {
        $this->update([
            'paused_from'  => $from->copy()->startOfDay(),
            'paused_until' => $until?->copy()->startOfDay(),
        ]);
    }

    /**
     * Reprend le traitement aujourd'hui. Une pause déjà commencée est close
     * hier, pour que les jours sautés restent visibles comme tels dans
     * l'historique ; une pause pas encore commencée est simplement annulée.
     */
    public function resume(): void
    {
        if ($this->paused_from === null) return;

        if ($this->paused_from->lt(Carbon::today())) {
            $this->update(['paused_until' => Carbon::yesterday()]);
        } else {
            $this->update(['paused_from' => null, 'paused_until' => null]);
        }
    }

    /** La pause propre à ce traitement couvre-t-elle cette date ? */
    public function ownPauseCovers(Carbon $date): bool
    {
        if ($this->paused_from === null) return false;

        $day = $date->toDateString();

        return $this->paused_from->toDateString() <= $day
            && ($this->paused_until === null || $this->paused_until->toDateString() >= $day);
    }

    /** En pause ce jour-là, directement ou via son traitement parent. */
    public function isPausedOn(Carbon $date): bool
    {
        if ($this->ownPauseCovers($date)) return true;

        return $this->parent_treatment_id !== null
            && (bool) $this->parentTreatment?->ownPauseCovers($date);
    }

    public function isPaused(): bool
    {
        return $this->isPausedOn(Carbon::today());
    }

    /** Pause en cours ou à venir (pas une pause déjà terminée). */
    public function hasPendingPause(): bool
    {
        return $this->paused_from !== null
            && ($this->paused_until === null || $this->paused_until->gte(Carbon::today()));
    }

    /**
     * Premier jour après la pause en cours ou à venir, à partir de $from :
     * null si la pause n'a pas de date de reprise. Renvoie $from s'il n'est
     * pas couvert par une pause.
     */
    public function resumeDateFrom(Carbon $from): ?Carbon
    {
        $parent = $this->parent_treatment_id !== null ? $this->parentTreatment : null;
        $day = $from->copy()->startOfDay();

        // Les deux pauses (propre / parent) peuvent se chevaucher : on avance
        // jusqu'à un jour couvert par aucune des deux.
        for ($i = 0; $i < 3; $i++) {
            foreach ([$this, $parent] as $t) {
                if ($t && $t->ownPauseCovers($day)) {
                    if ($t->paused_until === null) return null;
                    $day = $t->paused_until->copy()->addDay()->startOfDay();
                }
            }
        }

        return $day;
    }

    /**
     * Contrainte SQL : la pause de ce traitement couvre la date $dateColumn
     * (colonne de la requête englobante, ex. calendar_events.scheduled_date).
     */
    public function scopePauseCovers(Builder $query, string $dateColumn): void
    {
        $query->whereNotNull('paused_from')
            ->whereRaw("DATE(paused_from) <= DATE({$dateColumn})")
            ->where(fn (Builder $q) => $q->whereNull('paused_until')
                ->orWhereRaw("DATE(paused_until) >= DATE({$dateColumn})"));
    }

    public function posologyHistory(): HasMany
    {
        return $this->hasMany(PosologyHistory::class)->orderByDesc('started_at')->orderByDesc('id');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    public function parentTreatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class, 'parent_treatment_id');
    }

    public function childTreatments(): HasMany
    {
        return $this->hasMany(Treatment::class, 'parent_treatment_id');
    }

    public function isDaily(): bool
    {
        return $this->type === 'daily';
    }

    public function isDosageEditable(): bool
    {
        return !$this->is_medical_act;
    }

    public function requiresFasting(): bool
    {
        return (bool) $this->requires_fasting;
    }

    public function hasDayPartDoses(): bool
    {
        return $this->dose_morning !== null
            || $this->dose_noon !== null
            || $this->dose_evening !== null;
    }

    public function hasIntervalDose(): bool
    {
        return $this->times_per_day !== null && $this->times_per_day > 1;
    }

    public function intervalHours(): ?int
    {
        if (!$this->hasIntervalDose() || $this->times_per_day === 0) return null;
        $h = 24 / $this->times_per_day;
        return (int) $h === $h ? (int) $h : null;
    }

    public function dayOfWeekName(): string
    {
        if ($this->day_of_week === null || $this->day_of_week < 0 || $this->day_of_week > 6) {
            return '';
        }

        // 0 = lundi … 6 = dimanche. Le 2024-01-01 est un lundi : on s'appuie
        // sur Carbon pour obtenir le nom du jour dans la locale active.
        return Carbon::createFromDate(2024, 1, 1)
            ->addDays($this->day_of_week)
            ->isoFormat('dddd');
    }

    public function displayName(): string
    {
        return $this->commercial_name ?: $this->name;
    }
}
