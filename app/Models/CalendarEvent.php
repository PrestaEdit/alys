<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $fillable = [
        'treatment_id', 'scheduled_date', 'original_date', 'is_cancelled', 'notes',
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

    public function hasMoved(): bool
    {
        return $this->original_date !== null;
    }
}
