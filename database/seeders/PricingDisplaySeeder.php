<?php

namespace Database\Seeders;

use App\Models\PricingDisplay;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class PricingDisplaySeeder extends Seeder
{
    public function run(): void
    {
        $displays = [
            'trial' => [
                'badge' => null,
                'highlight_color' => null,
                'description_short' => 'Try Reco free for 14 days. No credit card required.',
                'description_long' => 'Perfect for individuals and small teams who want to explore Reco before committing. Includes all basic accounting features with limited capacity.',
                'features_list' => [
                    'Up to 2 users',
                    '100 transactions/month',
                    '30 accounts & 30 parties',
                    'Basic financial reports',
                    'Voucher management',
                    'Party management',
                    'Email support',
                ],
                'sort_order' => 0,
            ],
            'basic' => [
                'badge' => null,
                'highlight_color' => null,
                'description_short' => 'Essential tools for small businesses getting started with accounting.',
                'description_long' => 'Ideal for freelancers and small businesses. Includes everything in Trial plus export capabilities and higher limits.',
                'features_list' => [
                    'Up to 5 users',
                    '1,000 transactions/month',
                    '100 accounts & 100 parties',
                    'All basic reports',
                    'Voucher & party management',
                    'PDF export',
                    'Excel export',
                    'Priority email support',
                ],
                'sort_order' => 1,
            ],
            'professional' => [
                'badge' => 'Most Popular',
                'highlight_color' => '#6366f1',
                'description_short' => 'Full-featured accounting for growing businesses with advanced needs.',
                'description_long' => 'Built for growing businesses that need invoicing, inventory tracking, multi-user access, and audit logs. Everything you need to scale.',
                'features_list' => [
                    'Up to 15 users',
                    '10,000 transactions/month',
                    '500 accounts & 500 parties',
                    'All reports including AR/AP aging',
                    'Sales & purchase invoices',
                    'Inventory management',
                    'Multi-user with roles',
                    'Audit logs',
                    'PDF & Excel export',
                    'Phone & email support',
                ],
                'sort_order' => 2,
            ],
            'enterprise' => [
                'badge' => 'Best Value',
                'highlight_color' => '#10b981',
                'description_short' => 'Unlimited access for large organizations with premium support.',
                'description_long' => 'No limits. Everything unlimited with dedicated support, custom integrations, and priority feature requests. Perfect for enterprises and accounting firms.',
                'features_list' => [
                    'Unlimited users',
                    'Unlimited transactions',
                    'Unlimited accounts & parties',
                    'All reports + custom reports',
                    'Sales & purchase invoices',
                    'Inventory management',
                    'Multi-user with custom roles',
                    'Full audit trail',
                    'All export formats',
                    'Dedicated account manager',
                    'Phone, email & chat support',
                    'Custom integrations',
                ],
                'sort_order' => 3,
            ],
        ];

        foreach ($displays as $planSlug => $displayData) {
            $plan = SubscriptionPlan::where('slug', $planSlug)->first();

            if ($plan) {
                PricingDisplay::updateOrCreate(
                    ['plan_id' => $plan->id],
                    array_merge($displayData, [
                        'is_active' => true,
                    ])
                );
            }
        }

        $this->command->info('PricingDisplay seeder: ' . PricingDisplay::count() . ' records created.');
    }
}
