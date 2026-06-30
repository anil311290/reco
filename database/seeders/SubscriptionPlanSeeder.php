<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Trial',
                'slug' => 'trial',
                'description' => 'Free 14-day trial with basic features',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'lifetime_price' => 0,
                'trial_days' => 14,
                'max_users' => 2,
                'max_transactions' => 100,
                'max_accounts' => 30,
                'max_parties' => 30,
                'features' => ['basic_reports', 'voucher_management', 'party_management'],
                'sort_order' => 0,
                'is_active' => true,
                'is_default' => true,
                'is_visible' => true,
            ],
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Essential accounting for small businesses',
                'monthly_price' => 499,
                'yearly_price' => 4999,
                'lifetime_price' => 4999,
                'trial_days' => 0,
                'max_users' => 5,
                'max_transactions' => 1000,
                'max_accounts' => 100,
                'max_parties' => 100,
                'features' => ['basic_reports', 'voucher_management', 'party_management', 'export_pdf', 'export_excel'],
                'sort_order' => 1,
                'is_active' => true,
                'is_default' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'Full-featured accounting for growing businesses',
                'monthly_price' => 999,
                'yearly_price' => 9999,
                'lifetime_price' => 9999,
                'trial_days' => 0,
                'max_users' => 15,
                'max_transactions' => 10000,
                'max_accounts' => 500,
                'max_parties' => 500,
                'features' => ['all_reports', 'voucher_management', 'party_management', 'export_pdf', 'export_excel', 'sales_invoices', 'purchase_invoices', 'inventory', 'multi_user', 'audit_logs'],
                'sort_order' => 2,
                'is_active' => true,
                'is_default' => false,
                'is_visible' => true,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Unlimited access for large organizations',
                'monthly_price' => 1999,
                'yearly_price' => 19999,
                'lifetime_price' => 19999,
                'trial_days' => 0,
                'max_users' => -1,
                'max_transactions' => -1,
                'max_accounts' => -1,
                'max_parties' => -1,
                'features' => ['all'],
                'sort_order' => 3,
                'is_active' => true,
                'is_default' => false,
                'is_visible' => true,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
