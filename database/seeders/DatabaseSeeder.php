<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create default company
        $company = Company::firstOrCreate(
            ['slug' => 'ledgerpro-demo'],
            [
                'name' => 'LedgerPro Demo Company',
                'email' => 'demo@ledgerpro.com',
                'phone' => '+91 9876543210',
                'address' => '123 Business Street',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'country' => 'India',
                'postal_code' => '400001',
                'gst_number' => '27AAPFU0939F1ZV',
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'financial_year_start' => '04-01',
                'financial_year_end' => '03-31',
                'is_active' => true,
                'created_by_ip' => '127.0.0.1',
                'updated_by_ip' => '127.0.0.1',
            ]
        );

        // Create admin user
        $admin = User::firstOrCreate(
            ['email' => 'superadmin@reco.app'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('12345678'),
                'company_id' => $company->id,
                'phone' => '+91 9876543210',
            'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
                'created_by_ip' => '127.0.0.1',
                'updated_by_ip' => '127.0.0.1',
            ]
        );

        // Update company with admin as creator
        $company->update([
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        // Create manager user
        User::firstOrCreate(
            ['email' => 'manager@ledgerpro.com'],
            [
                'name' => 'Manager User',
                'password' => Hash::make('12345678'),
                'company_id' => $company->id,
                'phone' => '+91 9876543211',
                'role' => 'manager',
                'status' => 'active',
                'email_verified_at' => now(),
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'created_by_ip' => '127.0.0.1',
                'updated_by_ip' => '127.0.0.1',
            ]
        );
        User::firstOrCreate(
            ['email' => 'accountant@ledgerpro.com'],
            [
                'name' => 'Accountant User',
                'password' => Hash::make('12345678'),
                'company_id' => $company->id,
                'phone' => '+91 9876543212',
                'role' => 'accountant',
                'status' => 'active',
                'email_verified_at' => now(),
                'created_by' => $admin->id,
                'updated_by' => $admin->id,
                'created_by_ip' => '127.0.0.1',
                'updated_by_ip' => '127.0.0.1',
            ]
        );

        // Run additional seeders
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            SubscriptionPlanSeeder::class,
            PricingDisplaySeeder::class,
            ThemeSeeder::class,
            LocationSeeder::class,
            FinancialYearSeeder::class,
            AccountSeeder::class,
            TaxRateSeeder::class,
            PartySeeder::class,
            VoucherSeeder::class,
            WebsitePageSeeder::class,
            FaqSeeder::class,
            TestimonialSeeder::class,
        ]);

        $roleAssignments = [
            'superadmin@reco.app' => ['superadmin', 'admin'],
            'manager@ledgerpro.com' => ['manager'],
            'accountant@ledgerpro.com' => ['accountant'],
        ];

        foreach ($roleAssignments as $email => $roleSlugs) {
            $user = User::where('email', $email)->first();

            if (!$user) {
                continue;
            }

            $roleIds = Role::whereIn('slug', $roleSlugs)->pluck('id')->all();

            if (!empty($roleIds)) {
                $user->roles()->syncWithoutDetaching($roleIds);
            }
        }
    }
}
