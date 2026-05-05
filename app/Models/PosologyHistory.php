<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosologyHistory extends Model
{
    protected $table = 'posology_history';

    protected $fillable = [
        'treatment_id', 'dose', 'dose_morning', 'dose_noon', 'dose_evening', 'note', 'started_at',
    ];

    protected $casts = [
        'dose'         => 'decimal:2',
        'dose_morning' => 'decimal:2',
        'dose_noon'    => 'decimal:2',
        'dose_evening' => 'decimal:2',
        'started_at'   => 'date',
    ];

    public function treatment(): BelongsTo
    {
        return $this->belongsTo(Treatment::class);
    }
}
