<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\FinancialYear;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FinancialYearSeeder extends Seeder
{
    /**
     * Seed the financial year that covers today's date, based on the company's
     * configured financial-year start (defaults to 01 April).
     */
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            return;
        }

        [$startMonth, $startDay] = array_map(
            'intval',
            explode('-', $company->financial_year_start ?: '04-01')
        );

        $today = Carbon::today();
        $fyStartThisYear = Carbon::create($today->year, $startMonth, $startDay)->startOfDay();
        $startYear = $today->lt($fyStartThisYear) ? $today->year - 1 : $today->year;

        $start = Carbon::create($startYear, $startMonth, $startDay)->startOfDay();
        $end = $start->copy()->addYear()->subDay();
        $name = $startYear . '-' . ($startYear + 1);

        FinancialYear::firstOrCreate(
            ['company_id' => $company->id, 'name' => $name],
            [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'is_current' => true,
                'is_closed' => false,
            ]
        );
    }
}
