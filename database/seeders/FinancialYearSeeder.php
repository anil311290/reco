<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\FinancialYear;
use Illuminate\Database\Seeder;

class FinancialYearSeeder extends Seeder
{
    /**
     * Seed the financial years.
     */
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            return;
        }

        // Keep only the current financial year needed for fresh seed data.
        FinancialYear::firstOrCreate(
            ['company_id' => $company->id, 'name' => '2025-2026'],
            [
                'start_date' => '2025-04-01',
                'end_date' => '2026-03-31',
                'is_current' => true,
                'is_closed' => false,
            ]
        );
    }
}
