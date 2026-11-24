<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique des pesées d'un profil. Volontairement sans le scope du profil
 * actif : on lit et écrit toujours via la relation Profile::weightEntries().
 */
class WeightEntry extends Model
{
    protected $fillable = ['profile_id', 'weight_kg', 'measured_on'];

    protected $casts = [
        'weight_kg'   => 'decimal:1',
        'measured_on' => 'date',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
