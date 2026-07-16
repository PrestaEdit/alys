<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CalendarEvent extends Model
{
    use BelongsToActiveProfile;

    protected $fillable = [
        'profile_id',
        'treatment_id', 'scheduled_date', 'original_date', 'is_cancelled', 'notes', 'parent_event_id',
        'skip_morning', 'skip_noon', 'skip_evening',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'original_date' => 'date',
        'is_cancelled' => 'boolean',
        'skip_morning' => 'boolean',
        'skip_noon' => 'boolean',
        'skip_evening' => 'boolean',
    ];

    public function isDaypartSkipped(string $daypart): bool
    {
        return match ($daypart) {
            'morning' => (bool) $this->skip_morning,
            'noon'    => (bool) $this->skip_noon,
            'evening' => (bool) $this->skip_evening,
            default   => false,
        };
    }

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
