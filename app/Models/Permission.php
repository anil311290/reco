<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends Model
{
    use HasFactory, HasAuditFields, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'module',
        'description',
        'is_active',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'deleted_by',
        'deleted_by_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the roles that have the permission.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Scope: Get permissions by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Get active permissions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
