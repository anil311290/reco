<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'What is Reco?',
                'answer' => 'Reco is an offline-first accounting platform designed for small and medium businesses. It helps you manage invoices, vouchers, ledger entries, receivables, payables, and generate financial reports — all from one place.',
                'category' => 'General',
                'sort_order' => 1,
            ],
            [
                'question' => 'Does Reco work without internet?',
                'answer' => 'Yes! Reco is built with an offline-first architecture. You can continue working without an internet connection. Your data will automatically sync when you reconnect.',
                'category' => 'General',
                'sort_order' => 2,
            ],
            [
                'question' => 'Is there a free trial available?',
                'answer' => 'Yes, we offer a 14-day free trial on all plans. No credit card required to start. You can explore all features during the trial period.',
                'category' => 'Billing',
                'sort_order' => 3,
            ],
            [
                'question' => 'Can I upgrade or downgrade my plan?',
                'answer' => 'Absolutely! You can upgrade or downgrade your plan at any time. When upgrading, you will be charged the prorated difference. When downgrading, the change takes effect at the next billing cycle.',
                'category' => 'Billing',
                'sort_order' => 4,
            ],
            [
                'question' => 'What reports can I generate?',
                'answer' => 'Reco supports a wide range of financial reports including Balance Sheet, Profit & Loss Statement, Trial Balance, Cash Flow Statement, Day Book, Detailed Ledger, AR Aging, and AP Aging reports. All reports can be exported to PDF and Excel.',
                'category' => 'Features',
                'sort_order' => 5,
            ],
            [
                'question' => 'Is my data secure?',
                'answer' => 'Yes. We use industry-standard encryption for data at rest and in transit. We also support role-based access control, audit logging, and regular backups to ensure your data is always safe.',
                'category' => 'Security',
                'sort_order' => 6,
            ],
            [
                'question' => 'Can multiple users access the same account?',
                'answer' => 'Yes, depending on your plan. Each plan has a maximum number of users. You can assign different roles and permissions to each user to control access.',
                'category' => 'Features',
                'sort_order' => 7,
            ],
            [
                'question' => 'How do I get support?',
                'answer' => 'You can reach us through the contact form on our website, email us at support@reco.app, or call us during business hours. We also have a comprehensive FAQ and documentation section.',
                'category' => 'Support',
                'sort_order' => 8,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::firstOrCreate(
                ['question' => $faq['question']],
                $faq
            );
        }
    }
}
