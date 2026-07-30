<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Services\CompanyRoleService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    public function __construct(protected CompanyRoleService $companyRoleService)
    {
    }

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
        $superAdminRole->permissions()->syncWithoutDetaching($allPermissions->pluck('id')->all());

        if (Company::count() === 0) {
            return;
        }

        Company::query()->each(function (Company $company) {
            $this->companyRoleService->provisionDefaultRoles($company);
        });
    }
}
