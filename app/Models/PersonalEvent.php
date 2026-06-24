<?php

namespace App\Models;

use App\Models\Concerns\BelongsToActiveProfile;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PersonalEvent extends Model
{
    use BelongsToActiveProfile;

    protected $fillable = [
        'profile_id', 'title', 'category', 'color', 'icon', 'notes', 'start_date', 'end_date',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /** Catégories prédéfinies → icône + couleur par défaut (modifiables ensuite). */
    public const CATEGORIES = [
        'vacances'  => ['icon' => '🏖️', 'color' => '#0ea5e9'],
        'excursion' => ['icon' => '🚌', 'color' => '#10b981'],
        'autre'     => ['icon' => '📌', 'color' => '#f59e0b'],
    ];

    /** Emojis proposés dans le sélecteur d'icône. */
    public const ICONS = [
        '🏖️', '🚌', '✈️', '🏕️', '⛰️', '🏊', '🎉', '🎂', '🎄', '🏠', '🚗', '📌',
    ];

    /** Événements dont la plage [start_date, end_date] chevauche le mois donné. */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $end   = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        return $query->whereDate('start_date', '<=', $end)
                     ->whereDate('end_date', '>=', $start);
    }
}
