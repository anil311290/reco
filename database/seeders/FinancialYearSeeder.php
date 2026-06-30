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

        // Create current financial year (2025-2026)
        FinancialYear::firstOrCreate(
            ['company_id' => $company->id, 'name' => '2025-2026'],
            [
                'start_date' => '2025-04-01',
                'end_date' => '2026-03-31',
                'is_current' => true,
                'is_closed' => false,
            ]
        );

        // Create previous financial year (2024-2025)
        FinancialYear::firstOrCreate(
            ['company_id' => $company->id, 'name' => '2024-2025'],
            [
                'start_date' => '2024-04-01',
                'end_date' => '2025-03-31',
                'is_current' => false,
                'is_closed' => true,
                'closed_at' => '2025-04-01',
            ]
        );
    }
}
