<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\Rule;

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

    public function weightEntries(): HasMany
    {
        return $this->hasMany(WeightEntry::class);
    }

    public function heightEntries(): HasMany
    {
        return $this->hasMany(HeightEntry::class);
    }

    /**
     * Règles et messages de validation de la fiche médicale, pour les noms de
     * champs du composant appelant.
     *
     * @return array{0: array<string, mixed>, 1: array<string, string>}
     */
    public static function medicalValidation(string $weight, string $height, string $bloodGroup): array
    {
        return [
            [
                $weight     => 'nullable|numeric|min:1|max:500',
                $height     => 'nullable|integer|min:30|max:250',
                $bloodGroup => ['nullable', Rule::in(self::BLOOD_GROUPS)],
            ],
            [
                "$weight.numeric"   => __('profiles.validation_weight_range'),
                "$weight.min"       => __('profiles.validation_weight_range'),
                "$weight.max"       => __('profiles.validation_weight_range'),
                "$height.integer"   => __('profiles.validation_height_range'),
                "$height.min"       => __('profiles.validation_height_range'),
                "$height.max"       => __('profiles.validation_height_range'),
                "$bloodGroup.in"    => __('profiles.validation_blood_group_in'),
            ],
        ];
    }

    /**
     * Met à jour la fiche médicale. Un poids ou une taille qui change est
     * consigné dans son historique à la date du jour (une mesure par jour).
     */
    public function updateMedical(?float $weightKg, ?int $heightCm, ?string $bloodGroup): void
    {
        $previousWeight = $this->weight_kg !== null ? round((float) $this->weight_kg, 1) : null;
        $previousHeight = $this->height_cm;
        $weightKg = $weightKg !== null ? round($weightKg, 1) : null;

        $this->update([
            'weight_kg'   => $weightKg,
            'height_cm'   => $heightCm,
            'blood_group' => $bloodGroup,
        ]);

        if ($weightKg !== null && $weightKg !== $previousWeight) {
            self::recordToday($this->weightEntries(), ['weight_kg' => $weightKg]);
        }
        if ($heightCm !== null && $heightCm !== $previousHeight) {
            self::recordToday($this->heightEntries(), ['height_cm' => $heightCm]);
        }
    }

    /**
     * Crée ou corrige la mesure du jour. whereDate : la colonne peut contenir
     * « Y-m-d » ou « Y-m-d H:i:s » selon qu'elle vient du cast Eloquent ou
     * d'un insert brut (migration de reprise, import).
     */
    private static function recordToday(HasMany $entries, array $values): void
    {
        $today = (clone $entries)->whereDate('measured_on', today())->first();
        $today
            ? $today->update($values)
            : $entries->create($values + ['measured_on' => today()]);
    }
}
