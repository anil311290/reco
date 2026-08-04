<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\State;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyScopedCodesApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_can_create_party_when_another_company_owns_same_codes(): void
    {
        [$otherCompany] = $this->seedCompanyWithSystemAccounts();
        Party::factory()->create([
            'company_id' => $otherCompany->id,
            'party_code' => 'AR001',
            'type' => 'debtor',
            'opening_balance' => 0,
        ]);
        Voucher::factory()->create([
            'company_id' => $otherCompany->id,
            'voucher_number' => 'ADJ000001',
            'voucher_type' => 'adjustment',
        ]);

        [$company, $user, $state, $city] = $this->seedCompanyWithSystemAccounts();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/parties/by-type?type=debtor')
            ->assertOk()
            ->assertJsonPath('data.next_party_code', 'AR001');

        $response = $this->postJson('/api/v1/parties', [
            'name' => 'Debtor',
            'type' => 'debtor',
            'address' => 'Mysore',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'postal_code' => '570004',
            'opening_balance' => 5500,
            'opening_balance_type' => 'debit',
            'opening_date' => '2026-07-30',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.party_code', 'AR001');

        $this->assertDatabaseHas('parties', [
            'company_id' => $company->id,
            'party_code' => 'AR001',
            'name' => 'Debtor',
        ]);
        $this->assertDatabaseHas('vouchers', [
            'company_id' => $company->id,
            'voucher_number' => 'ADJ000001',
            'voucher_type' => 'adjustment',
        ]);
        $this->assertSame(2, Voucher::where('voucher_number', 'ADJ000001')->count());
    }

    public function test_api_can_create_account_opening_voucher_when_another_company_owns_adj000001(): void
    {
        [$otherCompany] = $this->seedCompanyWithSystemAccounts();
        Voucher::factory()->create([
            'company_id' => $otherCompany->id,
            'voucher_number' => 'ADJ000001',
            'voucher_type' => 'adjustment',
        ]);

        [$company, $user] = $this->seedCompanyWithSystemAccounts();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/accounts', [
            'account_name' => 'Furniture',
            'account_type' => 'asset',
            'opening_balance' => 5500,
            'balance_type' => 'debit',
            'opening_date' => '2026-07-30',
            'is_active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('data.account_name', 'Furniture');

        $this->assertDatabaseHas('vouchers', [
            'company_id' => $company->id,
            'voucher_number' => 'ADJ000001',
            'voucher_type' => 'adjustment',
        ]);
    }

    /**
     * @return array{0: Company, 1: User, 2: State, 3: City}
     */
    private function seedCompanyWithSystemAccounts(): array
    {
        $company = Company::factory()->create();
        $year = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
        ]);
        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        foreach ([
            [Account::CODE_AR, 'Accounts Receivable', 'asset'],
            [Account::CODE_AP, 'Accounts Payable', 'liability'],
            [Account::CODE_SUSPENSE, 'Opening Balance', 'asset'],
        ] as [$code, $name, $type]) {
            Account::factory()->create([
                'company_id' => $company->id,
                'financial_year_id' => $year->id,
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'opening_balance' => 0,
                'is_system' => true,
            ]);
        }

        $country = Country::create([
            'name' => 'India-' . $company->id,
            'iso2' => 'I' . substr((string) $company->id, -1),
            'iso3' => 'IN' . $company->id,
            'phone_code' => '+91',
            'currency' => 'INR',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $state = State::create([
            'country_id' => $country->id,
            'name' => 'Karnataka-' . $company->id,
            'code' => 'KA' . $company->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $city = City::create([
            'country_id' => $country->id,
            'state_id' => $state->id,
            'name' => 'Mysore-' . $company->id,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        return [$company, $user, $state, $city];
    }
}
