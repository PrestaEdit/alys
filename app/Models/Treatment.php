<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveProfile;
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
        'dose_morning', 'dose_noon', 'dose_evening',
        'color', 'frequency_weeks', 'is_medical_act', 'requires_fasting',
        'day_of_week', 'recurrence_start', 'notes',
        'show_widget', 'widget_icon',
        'parent_treatment_id', 'linked_days',
        'archived_at',
    ];

    protected $casts = [
        'current_dose'  => 'decimal:2',
        'dose_morning'  => 'decimal:2',
        'dose_noon'     => 'decimal:2',
        'dose_evening'  => 'decimal:2',
        'recurrence_start' => 'date',
        'archived_at'      => 'datetime',
        'frequency_weeks'  => 'integer',
        'day_of_week'      => 'integer',
        'linked_days'      => 'integer',
        'is_medical_act'   => 'boolean',
        'requires_fasting' => 'boolean',
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

    public function posologyHistory(): HasMany
    {
        return $this->hasMany(PosologyHistory::class)->orderByDesc('started_at');
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

    public function displayName(): string
    {
        return $this->commercial_name ?: $this->name;
    }
}
