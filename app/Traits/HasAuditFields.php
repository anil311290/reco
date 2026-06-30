<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * @mixin Model
 * @method static void creating(\Closure $callback)
 * @method static void updating(\Closure $callback)
 * @method static void deleting(\Closure $callback)
 */
trait HasAuditFields
{
    /**
     * Boot the audit fields trait.
     */
    public static function bootHasAuditFields(): void
    {
        static::creating(function (Model $model): void {
            $model->created_by = Auth::id();
            $model->created_by_ip = Request::ip();
            $model->updated_by = Auth::id();
            $model->updated_by_ip = Request::ip();
        });

        static::updating(function (Model $model): void {
            $model->updated_by = Auth::id();
            $model->updated_by_ip = Request::ip();
        });

        static::deleting(function (Model $model): void {
            if ($model->isSoftDeleting()) {
                $model->deleted_by = Auth::user()?->name;
                $model->deleted_by_id = Auth::id();
            }
        });
    }

    /**
     * Check if model uses soft deletes.
     */
    protected function isSoftDeleting(): bool
    {
        return in_array(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            class_uses_recursive(static::class)
        );
    }

    /**
     * Get the user who created the record.
     */
    public function creator()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updater()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }

    /**
     * Get the user who deleted the record.
     */
    public function deleter()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'deleted_by_id');
    }
}
