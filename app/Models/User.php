<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasAuditFields, HasFactory, HasUuid, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'password',
        'company_id',
        'phone',
        'avatar',
        'role',
        'status',
        'last_login_at',
        'last_login_ip',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'deleted_by',
        'deleted_by_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Get the company that owns the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->role === 'superadmin'
            || $this->roles()->where('slug', 'superadmin')->exists();
    }

    /**
     * Check if user is manager
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if user is accountant
     */
    public function isAccountant(): bool
    {
        return $this->role === 'accountant';
    }

    /**
     * Check if user is active (approved)
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if user is pending approval
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Get the roles that belong to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    /**
     * Check if user has role
     */
    public function hasRole(string $roleSlug): bool
    {
        if ($this->isSuperAdmin() && $roleSlug === 'superadmin') {
            return true;
        }

        if ($this->role === $roleSlug) {
            return true;
        }

        return $this->roles()->where('slug', $roleSlug)->exists();
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roleSlugs): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (in_array($this->role, $roleSlugs, true)) {
            return true;
        }

        return $this->roles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Check if user has permission
     */
    public function hasPermission(string $permissionSlug): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlug) {
                $query->where('slug', $permissionSlug);
            })
            ->exists()) {
            return true;
        }

        $legacyRole = $this->getLegacyRole();

        if ($legacyRole) {
            return $legacyRole->hasPermission($permissionSlug);
        }

        return false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissionSlugs): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if ($this->roles()
            ->whereHas('permissions', function ($query) use ($permissionSlugs) {
                $query->whereIn('slug', $permissionSlugs);
            })
            ->exists()) {
            return true;
        }

        $legacyRole = $this->getLegacyRole();

        if ($legacyRole) {
            return $legacyRole->permissions()->whereIn('slug', $permissionSlugs)->exists();
        }

        return false;
    }

    /**
     * Assign role to user
     */
    public function assignRole(Role $role): void
    {
        $this->roles()->syncWithoutDetaching($role);
    }

    /**
     * Remove role from user
     */
    public function removeRole(Role $role): void
    {
        $this->roles()->detach($role);
    }

    /**
     * Sync roles
     */
    public function syncRoles(array $roles): void
    {
        $this->roles()->sync($roles);
    }

    /**
     * Get all user permissions
     */
    public function getAllPermissions(): \Illuminate\Support\Collection
    {
        if ($this->isSuperAdmin()) {
            return Permission::all();
        }

        $roleIds = $this->roles()->pluck('roles.id');

        if ($legacyRole = $this->getLegacyRole()) {
            $roleIds->push($legacyRole->id);
        }

        $roleIds = $roleIds->unique()->filter()->values();

        if ($roleIds->isEmpty()) {
            return collect();
        }

        return Permission::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('roles.id', $roleIds);
        })->get();
    }

    /**
     * Resolve the legacy role stored on the users table.
     */
    protected function getLegacyRole(): ?Role
    {
        if (!$this->role || $this->role === 'superadmin') {
            return null;
        }

        return Role::query()
            ->where('slug', $this->role)
            ->where(function ($query) {
                $query->where('company_id', $this->company_id)
                    ->orWhereNull('company_id');
            })
            ->first();
    }
}
