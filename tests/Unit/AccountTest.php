<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use App\Services\AccountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected AccountService $accountService;
    protected Company $company;
    protected FinancialYear $financialYear;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->financialYear = FinancialYear::factory()->create([
            'company_id' => $this->company->id,
            'is_current' => true,
        ]);

        $this->accountService = $this->app->make(AccountService::class);
    }

    public function test_can_create_account(): void
    {
        $accountData = [
            'company_id' => $this->company->id,
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'transaction_mode' => 'cash',
        ];

        $account = $this->accountService->create($accountData);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('Cash', $account->account_name);
        $this->assertEquals('asset', $account->account_type);
        $this->assertNotNull($account->account_code);
    }

    public function test_account_code_is_auto_generated(): void
    {
        $account = $this->accountService->create([
            'company_id' => $this->company->id,
            'account_name' => 'Bank Account',
            'account_type' => 'asset',
            'transaction_mode' => 'bank',
        ]);

        // Numeric chart-of-accounts range (asset starts at 1000)
        $this->assertMatchesRegularExpression('/^\d{4}$/', (string) $account->account_code);
        $this->assertGreaterThanOrEqual(1000, (int) $account->account_code);
        $this->assertLessThan(2000, (int) $account->account_code);
    }

    public function test_can_create_account_with_transaction_mode(): void
    {
        $account = $this->accountService->create([
            'company_id' => $this->company->id,
            'account_name' => 'Cash in Hand',
            'account_type' => 'asset',
            'transaction_mode' => 'cash',
        ]);

        $this->assertEquals('cash', $account->transaction_mode);
        $this->assertEquals('Cash in Hand', $account->account_name);
    }

    public function test_can_update_account_status_and_remarks(): void
    {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_name' => 'Cash',
            'remarks' => 'Old',
        ]);

        $updated = $this->accountService->update($account->id, [
            'account_name' => 'Should Stay Immutable',
            'remarks' => 'Updated remarks',
        ]);

        $this->assertTrue($updated);
        $fresh = $account->fresh();
        $this->assertEquals('Cash', $fresh->account_name);
        $this->assertEquals('Updated remarks', $fresh->remarks);
    }

    public function test_transaction_mode_is_cleared_for_non_asset_accounts(): void
    {
        $account = $this->accountService->create([
            'company_id' => $this->company->id,
            'account_name' => 'Sales',
            'account_type' => 'income',
            'transaction_mode' => 'cash',
        ]);

        $this->assertNull($account->transaction_mode);
    }

    public function test_can_delete_non_system_account(): void
    {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'is_system' => false,
        ]);

        $deleted = $this->accountService->delete($account->id);

        $this->assertTrue($deleted);
        $this->assertNull(Account::find($account->id));
    }

    public function test_cannot_delete_system_account(): void
    {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'is_system' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->accountService->delete($account->id);
    }

    public function test_can_get_accounts_by_type(): void
    {
        Account::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'account_type' => 'asset',
        ]);

        Account::factory()->count(2)->create([
            'company_id' => $this->company->id,
            'account_type' => 'liability',
        ]);

        $assetAccounts = $this->accountService->getAll([
            'company_id' => $this->company->id,
            'account_type' => 'asset',
        ]);

        $this->assertCount(3, $assetAccounts);
    }

    public function test_account_type_label(): void
    {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => 'asset',
        ]);

        $this->assertEquals('Asset', $account->type_label);
    }

    public function test_can_toggle_account_status(): void
    {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
        ]);

        $this->accountService->update($account->id, ['is_active' => false]);

        $this->assertFalse($account->fresh()->is_active);
    }
}
