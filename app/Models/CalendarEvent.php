<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEvent extends Model
{
    protected $fillable = [
        'treatment_id', 'scheduled_date', 'original_date', 'is_cancelled', 'notes', 'parent_event_id',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'original_date' => 'date',
        'is_cancelled' => 'boolean',
    ];

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, 'parent_event_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(CalendarEvent::class, 'parent_event_id');
    }

    public function hasMoved(): bool
    {
        return $this->original_date !== null;
    }
}
