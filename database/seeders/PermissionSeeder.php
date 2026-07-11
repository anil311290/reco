<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the permissions.
     */
    public function run(): void
    {
        $permissions = [
            // Dashboard
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'module' => 'Dashboard'],

            // Users
            ['name' => 'View Users', 'slug' => 'users.view', 'module' => 'Users'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'module' => 'Users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'module' => 'Users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'module' => 'Users'],
            ['name' => 'Manage User Roles', 'slug' => 'users.manage-roles', 'module' => 'Users'],

            // Roles
            ['name' => 'View Roles', 'slug' => 'roles.view', 'module' => 'Roles'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'module' => 'Roles'],
            ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'module' => 'Roles'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'module' => 'Roles'],

            // Accounts
            ['name' => 'View Accounts', 'slug' => 'accounts.view', 'module' => 'Accounts'],
            ['name' => 'Create Accounts', 'slug' => 'accounts.create', 'module' => 'Accounts'],
            ['name' => 'Edit Accounts', 'slug' => 'accounts.edit', 'module' => 'Accounts'],
            ['name' => 'Delete Accounts', 'slug' => 'accounts.delete', 'module' => 'Accounts'],

            // Parties
            ['name' => 'View Parties', 'slug' => 'parties.view', 'module' => 'Parties'],
            ['name' => 'Create Parties', 'slug' => 'parties.create', 'module' => 'Parties'],
            ['name' => 'Edit Parties', 'slug' => 'parties.edit', 'module' => 'Parties'],
            ['name' => 'Delete Parties', 'slug' => 'parties.delete', 'module' => 'Parties'],

            // Tax Rates
            ['name' => 'View Tax Rates', 'slug' => 'tax-rates.view', 'module' => 'Tax Rates'],
            ['name' => 'Create Tax Rates', 'slug' => 'tax-rates.create', 'module' => 'Tax Rates'],
            ['name' => 'Edit Tax Rates', 'slug' => 'tax-rates.edit', 'module' => 'Tax Rates'],
            ['name' => 'Delete Tax Rates', 'slug' => 'tax-rates.delete', 'module' => 'Tax Rates'],

            // Vouchers
            ['name' => 'View Vouchers', 'slug' => 'vouchers.view', 'module' => 'Vouchers'],
            ['name' => 'Create Vouchers', 'slug' => 'vouchers.create', 'module' => 'Vouchers'],
            ['name' => 'Edit Vouchers', 'slug' => 'vouchers.edit', 'module' => 'Vouchers'],
            ['name' => 'Delete Vouchers', 'slug' => 'vouchers.delete', 'module' => 'Vouchers'],
            ['name' => 'Approve Vouchers', 'slug' => 'vouchers.approve', 'module' => 'Vouchers'],

            // Reports
            ['name' => 'View Reports', 'slug' => 'reports.view', 'module' => 'Reports'],
            ['name' => 'Export Reports', 'slug' => 'reports.export', 'module' => 'Reports'],

            // Settings
            ['name' => 'View Settings',          'slug' => 'settings.view',   'module' => 'Settings'],
            ['name' => 'Edit Settings',           'slug' => 'settings.edit',   'module' => 'Settings'],
            ['name' => 'Create Settings',         'slug' => 'settings.create', 'module' => 'Settings'],
            ['name' => 'Update Settings',         'slug' => 'settings.update', 'module' => 'Settings'],
            ['name' => 'Delete Settings',         'slug' => 'settings.delete', 'module' => 'Settings'],

            // Financial Years
            ['name' => 'View Financial Years', 'slug' => 'financial-years.view', 'module' => 'Financial Years'],
            ['name' => 'Create Financial Years', 'slug' => 'financial-years.create', 'module' => 'Financial Years'],
            ['name' => 'Close Financial Years', 'slug' => 'financial-years.close', 'module' => 'Financial Years'],

            // Audit Logs
            ['name' => 'View Audit Logs', 'slug' => 'audit-logs.view', 'module' => 'Audit Logs'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }
}
