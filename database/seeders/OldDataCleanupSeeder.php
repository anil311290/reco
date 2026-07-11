<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OldDataCleanupSeeder extends Seeder
{
    public function run(): void
    {
        $tables = [
            'voucher_lines',
            'journal_entries',
            'ledgers',
            'vouchers',
            'subscription_payments',
            'subscription_invoices',
            'subscriptions',
            'pricing_displays',
            'testimonials',
            'faqs',
            'website_pages',
            'parties',
            'accounts',
            'financial_years',
            'tax_rates',
            'locations',
            'cities',
            'states',
            'countries',
        ];

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            DB::table($table)->delete();
        }
    }
}
