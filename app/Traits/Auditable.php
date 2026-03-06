<?php

namespace App\Traits;

use App\Support\Audit\Audit;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            Audit::event('created', $model, [
                'attributes' => $model->getAttributes(),
            ]);
        });

        static::updated(function (Model $model) {
            Audit::event('updated', $model, [
                'changes' => $model->getChanges(),
                'original' => array_intersect_key(
                    $model->getOriginal(),
                    $model->getChanges()
                ),
            ]);
        });

        static::deleted(function (Model $model) {
            Audit::event('deleted', $model, [
                'attributes' => $model->getAttributes(),
            ]);
        });
    }
}
