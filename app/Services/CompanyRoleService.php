<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;

class CompanyRoleService
{
    /**
     * Create default company roles and sync permissions (idempotent per company).
     */
    public function provisionDefaultRoles(Company $company): void
    {
        $allPermissions = Permission::all();

        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin', 'company_id' => $company->id],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Administrator',
                'description' => 'Full company access including tax master CRUD',
                'is_default' => false,
                'is_active' => true,
            ]
        );
        $adminRole->syncPermissions($allPermissions->pluck('id')->toArray());

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager', 'company_id' => $company->id],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Manager',
                'description' => 'Most modules except settings and user management',
                'is_default' => false,
                'is_active' => true,
            ]
        );
        $managerPermissions = $allPermissions->filter(
            fn ($p) => ! in_array($p->module, ['Settings', 'Users', 'Roles'], true)
        );
        $managerRole->syncPermissions($managerPermissions->pluck('id')->toArray());

        $accountantRole = Role::firstOrCreate(
            ['slug' => 'accountant', 'company_id' => $company->id],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Accountant',
                'description' => 'Accounting modules, reports, and tax master',
                'is_default' => true,
                'is_active' => true,
            ]
        );
        $accountantPermissions = $allPermissions->filter(
            fn ($p) => in_array($p->module, ['Dashboard', 'Accounts', 'Parties', 'Vouchers', 'Reports', 'Tax Rates'], true)
        );
        $accountantRole->syncPermissions($accountantPermissions->pluck('id')->toArray());

        $viewerRole = Role::firstOrCreate(
            ['slug' => 'viewer', 'company_id' => $company->id],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'Viewer',
                'description' => 'Read-only access',
                'is_default' => false,
                'is_active' => true,
            ]
        );
        $viewerPermissions = $allPermissions->filter(
            fn ($p) => str_contains($p->slug, '.view') || str_contains($p->slug, 'reports.export')
        );
        $viewerRole->syncPermissions($viewerPermissions->pluck('id')->toArray());
    }

    /**
     * Assign the company administrator role to the tenant owner.
     */
    public function assignCompanyOwner(User $user): void
    {
        if (! $user->company_id) {
            return;
        }

        $adminRole = Role::query()
            ->where('company_id', $user->company_id)
            ->where('slug', 'admin')
            ->first();

        if (! $adminRole) {
            return;
        }

        if ($user->role !== 'admin') {
            $user->update(['role' => 'admin']);
        }

        $user->roles()->syncWithoutDetaching([$adminRole->id]);
    }
}
