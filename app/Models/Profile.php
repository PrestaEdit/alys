<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    public const COLORS = [
        '#0ea5e9', '#6366f1', '#10b981', '#f43f5e',
        '#f59e0b', '#8b5cf6', '#64748b', '#14b8a6',
    ];

    public const BLOOD_GROUPS = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    protected $fillable = [
        'name', 'color', 'icon',
        'treatment_start', 'treatment_end', 'archived_at',
        'weight_kg', 'height_cm', 'blood_group',
    ];

    protected $casts = [
        'treatment_start' => 'date',
        'treatment_end'   => 'date',
        'archived_at'     => 'datetime',
        'weight_kg'       => 'decimal:1',
        'height_cm'       => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    public function archive(): void
    {
        $this->update(['archived_at' => now()]);
    }

    public function unarchive(): void
    {
        $this->update(['archived_at' => null]);
    }

    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }
}
