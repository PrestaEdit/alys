<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Treatment extends Model
{
    protected $fillable = [
        'name', 'commercial_name', 'type', 'unit', 'current_dose',
        'color', 'frequency_weeks', 'is_medical_act', 'requires_fasting', 'day_of_week', 'recurrence_start', 'notes',
        'show_widget', 'widget_icon',
    ];

    protected $casts = [
        'current_dose' => 'decimal:2',
        'recurrence_start' => 'date',
        'frequency_weeks' => 'integer',
        'day_of_week' => 'integer',
        'is_medical_act' => 'boolean',
        'requires_fasting' => 'boolean',
    ];

    public function posologyHistory(): HasMany
    {
        return $this->hasMany(PosologyHistory::class)->orderByDesc('started_at');
    }

    public function calendarEvents(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
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

    public function displayName(): string
    {
        return $this->commercial_name ?: $this->name;
    }
}
