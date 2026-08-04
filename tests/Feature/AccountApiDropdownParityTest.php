<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountApiDropdownParityTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_dropdown_by_type_excludes_opening_balance_difference(): void
    {
        [$company, $user, $year] = $this->createContext();

        Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => Account::CODE_SUSPENSE,
            'account_name' => 'Opening Balance',
            'account_type' => 'asset',
            'is_system' => true,
            'is_active' => true,
        ]);

        $asset = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => '1001',
            'account_name' => 'Cash Counter',
            'account_type' => 'asset',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/accounts/by-type?type=asset');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => $asset->id])
            ->assertJsonMissing(['account_code' => Account::CODE_SUSPENSE])
            ->assertJsonMissing(['text' => Account::CODE_SUSPENSE . ' - Opening Balance']);
    }

    public function test_cash_bank_api_excludes_opening_balance_difference_even_if_flagged_cash_bank(): void
    {
        [$company, $user, $year] = $this->createContext();

        Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => Account::CODE_SUSPENSE,
            'account_name' => 'Opening Balance',
            'account_type' => 'asset',
            'is_system' => true,
            'is_active' => true,
            'is_cash_bank_od' => true,
        ]);

        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => '1002',
            'account_name' => 'Main Cash',
            'account_type' => 'asset',
            'is_active' => true,
            'is_cash_bank_od' => true,
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/accounts/cash-bank?financial_year_id=' . $year->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => $cash->id])
            ->assertJsonMissing(['id' => Account::CODE_SUSPENSE])
            ->assertJsonMissing(['text' => Account::CODE_SUSPENSE . ' - Opening Balance']);
    }

    public function test_payment_and_adjustment_particulars_exclude_reserved_ledgers_and_include_both_party_types(): void
    {
        [$company, $user, $year] = $this->createContext();

        Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => Account::CODE_SUSPENSE,
            'account_name' => 'Opening Balance',
            'account_type' => 'asset',
            'is_system' => true,
            'is_active' => true,
        ]);
        Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => Account::CODE_AR,
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'is_system' => true,
            'is_active' => true,
        ]);
        Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => Account::CODE_AP,
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'is_system' => true,
            'is_active' => true,
        ]);
        $expense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $year->id,
            'account_code' => '1752',
            'account_name' => 'Office Expense',
            'account_type' => 'expense',
            'is_active' => true,
        ]);

        $debtor = Party::factory()->create([
            'company_id' => $company->id,
            'type' => 'debtor',
            'name' => 'Debtor One',
            'party_code' => 'DBT001',
            'is_active' => true,
        ]);
        $creditor = Party::factory()->create([
            'company_id' => $company->id,
            'type' => 'creditor',
            'name' => 'Creditor One',
            'party_code' => 'CDT001',
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $payment = $this->getJson('/api/v1/accounts/payment-particulars?type=payment');
        $payment->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => 'party:' . $debtor->id])
            ->assertJsonFragment(['id' => 'party:' . $creditor->id])
            ->assertJsonFragment(['id' => (string) $expense->id])
            ->assertJsonMissing(['text' => Account::CODE_SUSPENSE . ' - Opening Balance'])
            ->assertJsonMissing(['text' => Account::CODE_AR . ' - Accounts Receivable'])
            ->assertJsonMissing(['text' => Account::CODE_AP . ' - Accounts Payable']);

        $adjustment = $this->getJson('/api/v1/accounts/adjustment-particulars');
        $adjustment->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonFragment(['id' => 'party:' . $debtor->id])
            ->assertJsonFragment(['id' => 'party:' . $creditor->id])
            ->assertJsonFragment(['id' => (string) $expense->id])
            ->assertJsonMissing(['text' => Account::CODE_SUSPENSE . ' - Opening Balance'])
            ->assertJsonMissing(['text' => Account::CODE_AR . ' - Accounts Receivable'])
            ->assertJsonMissing(['text' => Account::CODE_AP . ' - Accounts Payable']);
    }

    private function createContext(): array
    {
        $company = Company::factory()->create();
        $year = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
            'is_closed' => false,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        return [$company, $user, $year];
    }
}