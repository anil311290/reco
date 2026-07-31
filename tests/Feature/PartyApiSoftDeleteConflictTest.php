<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Company;
use App\Models\Country;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\State;
use App\Models\User;
use App\Services\PartyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PartyApiSoftDeleteConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_update_party_does_not_require_opening_balance_type(): void
    {
        $company = Company::factory()->create();
        FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $country = Country::create([
            'name' => 'India',
            'iso2' => 'IN',
            'iso3' => 'IND',
            'phone_code' => '+91',
            'currency' => 'INR',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $state = State::create([
            'country_id' => $country->id,
            'name' => 'Maharashtra',
            'code' => 'MH',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $city = City::create([
            'country_id' => $country->id,
            'state_id' => $state->id,
            'name' => 'Mumbai',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'party_code' => 'AR001',
            'name' => 'Original Name',
            'type' => 'debtor',
            'opening_balance' => 2500,
            'opening_balance_type' => 'debit',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'address' => 'Old address',
            'postal_code' => '400001',
        ]);

        Sanctum::actingAs($user);

        $this->putJson('/api/v1/parties/' . $party->id, [
            'name' => 'Updated Name',
            'type' => 'debtor',
            'mobile' => '9999999999',
            'address' => 'New address',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'postal_code' => '400001',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Party updated successfully')
            ->assertJsonPath('data.name', 'Updated Name');

        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'name' => 'Updated Name',
            'opening_balance_type' => 'debit',
        ]);
    }

    public function test_api_create_returns_conflict_for_soft_deleted_party_name(): void
    {
        $company = Company::factory()->create();
        FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
        ]);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $country = Country::create([
            'name' => 'India',
            'iso2' => 'IN',
            'iso3' => 'IND',
            'phone_code' => '+91',
            'currency' => 'INR',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $state = State::create([
            'country_id' => $country->id,
            'name' => 'Maharashtra',
            'code' => 'MH',
            'is_active' => true,
            'sort_order' => 1,
        ]);
        $city = City::create([
            'country_id' => $country->id,
            'state_id' => $state->id,
            'name' => 'Mumbai',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $partyService = app(PartyService::class);
        $party = Party::factory()->create([
            'company_id' => $company->id,
            'party_code' => 'AR001',
            'name' => 'Acme Customer',
            'type' => 'debtor',
            'opening_balance' => 0,
            'state_id' => $state->id,
            'city_id' => $city->id,
            'address' => 'Old address',
            'postal_code' => '400001',
        ]);
        $partyService->delete($party->id);

        Sanctum::actingAs($user);

        $payload = [
            'name' => 'Acme Customer',
            'type' => 'debtor',
            'address' => 'New address',
            'state_id' => $state->id,
            'city_id' => $city->id,
            'postal_code' => '400001',
            'opening_balance' => 0,
            'opening_balance_type' => 'debit',
        ];

        $this->postJson('/api/v1/parties', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'SOFT_DELETED_PARTY_EXISTS')
            ->assertJsonPath('data.party_code', 'AR001');

        $this->postJson('/api/v1/parties', array_merge($payload, [
            'duplicate_action' => 'restore',
        ]))
            ->assertOk()
            ->assertJsonPath('message', 'Party restored successfully')
            ->assertJsonPath('data.id', $party->id);

        $this->assertDatabaseHas('parties', [
            'id' => $party->id,
            'party_code' => 'AR001',
            'address' => 'New address',
            'deleted_at' => null,
        ]);
    }
}
