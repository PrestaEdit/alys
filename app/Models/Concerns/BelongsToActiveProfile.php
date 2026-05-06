<?php

namespace App\Models\Concerns;

use App\Models\Profile;
use App\Services\ActiveProfile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToActiveProfile
{
    public static function bootBelongsToActiveProfile(): void
    {
        static::addGlobalScope(new ActiveProfileScope);

        static::creating(function (Model $model) {
            if ($model->profile_id === null) {
                $id = app(ActiveProfile::class)->id();
                if ($id === null) {
                    throw new \RuntimeException(
                        'Cannot create '.static::class.' without an active profile.'
                    );
                }
                $model->profile_id = $id;
            }
        });
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
