<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $allPermissions = Permission::all();

        $superAdminRole = Role::firstOrCreate(
            ['slug' => 'superadmin'],
            [
                'uuid' => (string) Str::uuid(),
                'company_id' => null,
                'name' => 'Super Admin',
                'description' => 'Global access across all companies',
                'is_default' => false,
                'is_active' => true,
            ]
        );
        $superAdminRole->syncPermissions($allPermissions->pluck('id')->toArray());

        $company = Company::first();
        if (!$company) {
            return;
        }

        // Admin Role
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            [
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'name' => 'Administrator',
                'description' => 'Full access',
                'is_default' => false,
                'is_active' => true,
            ]
        );
        $adminRole->syncPermissions($allPermissions->pluck('id')->toArray());

        // Manager Role
        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            [
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'name' => 'Manager',
                'description' => 'Most modules except settings',
                'is_default' => false,
                'is_active' => true,
            ]
        );
        $managerPermissions = $allPermissions->filter(fn($p) => !in_array($p->module, ['Settings', 'Users', 'Roles']));
        $managerRole->syncPermissions($managerPermissions->pluck('id')->toArray());

        // Accountant Role
        $accountantRole = Role::firstOrCreate(
            ['slug' => 'accountant'],
            [
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'name' => 'Accountant',
                'description' => 'Accounting modules and reports',
                'is_default' => true,
                'is_active' => true,
            ]
        );
        $accountantPermissions = $allPermissions->filter(fn($p) => in_array($p->module, ['Dashboard', 'Accounts', 'Parties', 'Vouchers', 'Reports']));
        $accountantRole->syncPermissions($accountantPermissions->pluck('id')->toArray());

        // Viewer Role
        $viewerRole = Role::firstOrCreate(
            ['slug' => 'viewer'],
            [
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'name' => 'Viewer',
                'description' => 'Read-only access',
                'is_default' => false,
                'is_active' => true,
            ]
        );
        $viewerPermissions = $allPermissions->filter(fn($p) => str_contains($p->slug, '.view') || str_contains($p->slug, 'reports.export'));
        $viewerRole->syncPermissions($viewerPermissions->pluck('id')->toArray());
    }
}
