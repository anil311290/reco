<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * @mixin Model
 * @method static void creating(\Closure $callback)
 */
trait HasVersioning
{
    /**
     * Boot the HasVersioning trait.
     * Automatically called by Eloquent when the model is instantiated.
     */
    public static function bootHasVersioning(): void
    {
        static::creating(function (Model $model): void {
            // Only set if the table has the version column
            try {
                if (Schema::hasColumn($model->getTable(), 'version')) {
                    if (!isset($model->version)) {
                        $model->version = 1;
                    }
                    if (!isset($model->synced_at)) {
                        $model->synced_at = null;
                    }
                }
            } catch (\Exception $e) {
                // Schema may not be available in some contexts (tests); ignore safely
            }
        });
    }
}
