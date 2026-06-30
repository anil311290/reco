<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use HasFactory, HasAuditFields, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'slug',
        'description',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'deleted_by',
        'deleted_by_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the company that owns the role.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the users that belong to the role.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Get the permissions that belong to the role.
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()->where('slug', $permissionSlug)->exists();
    }

    /**
     * Give permission to role
     */
    public function givePermissionTo(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching($permission);
    }

    /**
     * Remove permission from role
     */
    public function revokePermissionTo(Permission $permission): void
    {
        $this->permissions()->detach($permission);
    }

    /**
     * Sync permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions()->sync($permissions);
    }
}
