<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RoleRequest;
use App\Services\RoleService;
use App\Helpers\ResponseHelper;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected RoleService $roleService;

    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }

    /**
     * Display roles list
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = [];
            if ($request->filled('is_active')) $filters['is_active'] = $request->input('is_active');
            $searchValue = $request->input('search.value', $request->input('search'));
            if (!empty($searchValue)) $filters['search'] = is_array($searchValue) ? ($searchValue['value'] ?? '') : $searchValue;
            $roles = $this->roleService->getPaginated($filters);

            return response()->json([
                'data' => $roles->items(),
                'recordsTotal' => $roles->total(),
                'recordsFiltered' => $roles->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        return view('admin.roles.index');
    }

    /**
     * Show create form
     */
    public function create()
    {
        $permissions = $this->roleService->getPermissionsGrouped();
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store new role
     */
    public function store(RoleRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $data['company_id'] = auth()->user()->company_id;

            $role = $this->roleService->create($data);

            return ResponseHelper::success($role, 'Role created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Show edit form
     */
    public function edit(int $id)
    {
        $role = $this->roleService->getById($id);
        $permissions = $this->roleService->getPermissionsGrouped();

        if (!$role) {
            return ResponseHelper::notFound('Role not found');
        }

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    /**
     * Update role
     */
    public function update(RoleRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();

            $updated = $this->roleService->update($id, $data);

            if (!$updated) {
                return ResponseHelper::notFound('Role not found');
            }

            return ResponseHelper::success(null, 'Role updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Delete role
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $deleted = $this->roleService->delete($id);

            if (!$deleted) {
                return ResponseHelper::notFound('Role not found');
            }

            return ResponseHelper::success(null, 'Role deleted successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Change role status
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|boolean',
        ]);

        try {
            $updated = $this->roleService->update($id, [
                'is_active' => $request->status,
            ]);

            if (!$updated) {
                return ResponseHelper::notFound('Role not found');
            }

            $statusText = $request->status ? 'activated' : 'deactivated';
            return ResponseHelper::success(null, "Role {$statusText} successfully");
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }
}
