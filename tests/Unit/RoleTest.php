<?php

namespace Tests\Unit;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RoleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    protected RoleService $roleService;
    protected Company $company;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->admin = User::factory()->create([
            'company_id' => $this->company->id,
            'role' => 'admin',
        ]);

        $this->roleService = $this->app->make(RoleService::class);
    }

    public function test_can_create_role(): void
    {
        $roleData = [
            'name' => 'Test Role',
            'slug' => 'test-role',
            'description' => 'A test role',
            'company_id' => $this->company->id,
        ];

        $role = $this->roleService->create($roleData);

        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('Test Role', $role->name);
        $this->assertEquals('test-role', $role->slug);
    }

    public function test_can_assign_permissions_to_role(): void
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);
        $permission = Permission::factory()->create();

        $role->givePermissionTo($permission);

        $this->assertTrue($role->hasPermission($permission->slug));
    }

    public function test_can_remove_permission_from_role(): void
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);
        $permission = Permission::factory()->create();

        $role->givePermissionTo($permission);
        $this->assertTrue($role->hasPermission($permission->slug));

        $role->revokePermissionTo($permission);
        $this->assertFalse($role->fresh()->hasPermission($permission->slug));
    }

    public function test_user_has_role(): void
    {
        $role = Role::factory()->create([
            'company_id' => $this->company->id,
            'slug' => 'test-role',
        ]);

        $this->admin->assignRole($role);

        $this->assertTrue($this->admin->hasRole('test-role'));
        $this->assertFalse($this->admin->hasRole('non-existent-role'));
    }

    public function test_user_has_permission_through_role(): void
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);
        $permission = Permission::factory()->create(['slug' => 'test-permission']);

        $role->givePermissionTo($permission);
        $this->admin->assignRole($role);

        $this->assertTrue($this->admin->hasPermission('test-permission'));
    }

    public function test_admin_has_all_permissions(): void
    {
        $permission = Permission::factory()->create();

        $this->assertTrue($this->admin->hasPermission($permission->slug));
    }

    public function test_role_can_be_set_as_default(): void
    {
        $role = Role::factory()->create([
            'company_id' => $this->company->id,
            'is_default' => true,
        ]);

        $this->assertTrue($role->is_default);
    }

    public function test_role_can_be_deactivated(): void
    {
        $role = Role::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => false,
        ]);

        $this->assertFalse($role->is_active);
    }

    public function test_can_get_roles_by_company(): void
    {
        Role::factory()->count(3)->create(['company_id' => $this->company->id]);
        
        $roles = $this->roleService->getAll(['company_id' => $this->company->id]);

        $this->assertCount(3, $roles);
    }

    public function test_can_update_role(): void
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);

        $updated = $this->roleService->update($role->id, [
            'name' => 'Updated Role',
        ]);

        $this->assertTrue($updated);
        $this->assertEquals('Updated Role', $role->fresh()->name);
    }

    public function test_can_delete_role_without_users(): void
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);

        $deleted = $this->roleService->delete($role->id);

        $this->assertTrue($deleted);
        $this->assertNull(Role::find($role->id));
    }

    public function test_cannot_delete_role_with_users(): void
    {
        $role = Role::factory()->create(['company_id' => $this->company->id]);
        $this->admin->assignRole($role);

        $this->expectException(\Exception::class);
        $this->roleService->delete($role->id);
    }
}
