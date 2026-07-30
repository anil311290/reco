<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountApiSoftDeleteConflictTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_can_restore_a_matching_soft_deleted_account(): void
    {
        [$company, $user, $service] = $this->createAccountContext();

        $account = $service->create([
            'company_id' => $company->id,
            'account_name' => 'Petty Cash',
            'account_type' => 'asset',
            'transaction_mode' => 'cash',
        ]);
        $originalCode = $account->account_code;
        $service->delete($account->id);

        Sanctum::actingAs($user);

        $payload = [
            'account_name' => 'Petty Cash',
            'account_type' => 'asset',
            'transaction_mode' => 'cash',
            'opening_balance' => 500,
            'balance_type' => 'debit',
            'opening_date' => '2026-07-30',
            'is_active' => true,
        ];

        $this->postJson('/api/v1/accounts', $payload)
            ->assertStatus(409)
            ->assertJsonPath('code', 'SOFT_DELETED_ACCOUNT_EXISTS')
            ->assertJsonPath('data.account_code', $originalCode);

        $this->postJson('/api/v1/accounts', [
            ...$payload,
            'duplicate_action' => 'restore',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Account restored successfully')
            ->assertJsonPath('data.id', $account->id);

        $this->assertDatabaseHas('accounts', [
            'id' => $account->id,
            'account_code' => $originalCode,
            'opening_balance' => 500,
            'deleted_at' => null,
        ]);
    }

    public function test_api_can_create_a_new_entry_instead_of_restoring_deleted_account(): void
    {
        [$company, $user, $service] = $this->createAccountContext();

        $deletedAccount = $service->create([
            'company_id' => $company->id,
            'account_name' => 'Clearing Ledger',
            'account_type' => 'asset',
        ]);
        $service->delete($deletedAccount->id);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/accounts', [
            'account_name' => 'Clearing Ledger',
            'account_type' => 'asset',
            'opening_balance' => 0,
            'balance_type' => 'debit',
            'duplicate_action' => 'new_entry',
            'is_active' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Account created successfully');

        $newAccount = Account::where('company_id', $company->id)
            ->where('account_name', 'Clearing Ledger')
            ->firstOrFail();

        $this->assertNotSame($deletedAccount->id, $newAccount->id);
        $this->assertNotSame($deletedAccount->account_code, $newAccount->account_code);
        $this->assertTrue(Account::onlyTrashed()->whereKey($deletedAccount->id)->exists());
    }

    public function test_api_account_list_exposes_deletion_control_fields(): void
    {
        [$company, $user, $service] = $this->createAccountContext();

        $account = $service->create([
            'company_id' => $company->id,
            'account_name' => 'Manual Ledger',
            'account_type' => 'expense',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/accounts')
            ->assertOk()
            ->assertJsonFragment([
                'id' => $account->id,
                'uuid' => $account->uuid,
                'version' => (int) $account->version,
                'entry_source' => 'manual',
                'is_system' => false,
            ]);
    }

    private function createAccountContext(): array
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

        return [$company, $user, app(AccountService::class)];
    }
}
