<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique des tailles d'un profil. Comme WeightEntry, sans le scope du
 * profil actif : on passe toujours par Profile::heightEntries().
 */
class HeightEntry extends Model
{
    protected $fillable = ['profile_id', 'height_cm', 'measured_on'];

    protected $casts = [
        'height_cm'   => 'integer',
        'measured_on' => 'date',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
