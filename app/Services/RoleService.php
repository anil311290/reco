<?php

namespace App\Services;

use App\Interfaces\RoleRepositoryInterface;
use App\Interfaces\PermissionRepositoryInterface;
use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class RoleService
{
    protected RoleRepositoryInterface $roleRepository;
    protected PermissionRepositoryInterface $permissionRepository;

    public function __construct(
        RoleRepositoryInterface $roleRepository,
        PermissionRepositoryInterface $permissionRepository
    ) {
        $this->roleRepository = $roleRepository;
        $this->permissionRepository = $permissionRepository;
    }

    /**
     * Get all roles with filters
     */
    public function getAll(array $filters = []): Collection
    {
        $query = Role::query();

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        return $query->get();
    }

    /**
     * Get paginated roles
     */
    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Role::with(['permissions']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('slug', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Get role by ID
     */
    public function getById(int $id): ?Role
    {
        return $this->roleRepository->find($id, ['*'], ['permissions']);
    }

    /**
     * Create role
     */
    public function create(array $data): Role
    {
        $role = $this->roleRepository->create($data);

        if (isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $role->load('permissions');
    }

    /**
     * Update role
     */
    public function update(int $id, array $data): bool
    {
        $role = $this->roleRepository->find($id);

        if (!$role) {
            return false;
        }

        $updated = $this->roleRepository->update($id, $data);

        if ($updated && isset($data['permissions'])) {
            $role->syncPermissions($data['permissions']);
        }

        return $updated;
    }

    /**
     * Delete role
     */
    public function delete(int $id): bool
    {
        $role = $this->roleRepository->find($id);

        if (!$role) {
            return false;
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            throw new \Exception('Cannot delete role with assigned users.');
        }

        return $this->roleRepository->delete($id);
    }

    /**
     * Get all permissions grouped by module
     */
    public function getPermissionsGrouped(): Collection
    {
        return $this->permissionRepository->getGrouped();
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(int $roleId, int $permissionId): bool
    {
        $role = $this->roleRepository->find($roleId);
        $permission = $this->permissionRepository->find($permissionId);

        if (!$role || !$permission) {
            return false;
        }

        $role->givePermissionTo($permission);
        return true;
    }

    /**
     * Remove permission from role
     */
    public function removePermission(int $roleId, int $permissionId): bool
    {
        $role = $this->roleRepository->find($roleId);
        $permission = $this->permissionRepository->find($permissionId);

        if (!$role || !$permission) {
            return false;
        }

        $role->revokePermissionTo($permission);
        return true;
    }
}
