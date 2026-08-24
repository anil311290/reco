<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialYearAccountSeedingTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_financial_year_seeds_required_system_ledgers(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson('/admin/financial-years', [
            'name' => 'FY 2027-28',
            'start_date' => '2027-04-01',
            'end_date' => '2028-03-31',
        ]);

        $response->assertOk();

        $financialYear = FinancialYear::query()
            ->where('company_id', $company->id)
            ->where('name', 'FY 2027-28')
            ->firstOrFail();

        foreach ([
            Account::CODE_SUSPENSE,
            Account::CODE_PURCHASE_TAX,
            Account::CODE_AR,
            Account::CODE_SALES_TAX,
            Account::CODE_AP,
            Account::CODE_AR_INCOME,
            Account::CODE_SERVICE_INCOME,
            Account::CODE_AP_EXPENSE,
        ] as $code) {
            $this->assertDatabaseHas('accounts', [
                'company_id' => $company->id,
                'account_code' => $code,
                'is_system' => 1,
                'is_active' => 1,
            ]);
        }
    }
}
