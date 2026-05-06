<?php

namespace App\Models\Concerns;

use App\Services\ActiveProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ActiveProfileScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $id = app(ActiveProfile::class)->id();
        if ($id !== null) {
            $builder->where($model->getTable().'.profile_id', $id);
        }
    }
}
