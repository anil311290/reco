<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @mixin Model
 * @method static void creating(\Closure $callback)
 */
trait HasUuid
{
    public static function bootHasUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }
}
