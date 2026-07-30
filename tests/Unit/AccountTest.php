<?php

namespace Tests\Unit;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Ledger;
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
            'account_name' => 'Renamed Cash Ledger',
            'remarks' => 'Updated remarks',
        ]);

        $this->assertTrue($updated);
        $fresh = $account->fresh();
        $this->assertEquals('Renamed Cash Ledger', $fresh->account_name);
        $this->assertEquals('Updated remarks', $fresh->remarks);
    }

    public function test_opening_balance_fields_cannot_be_updated_after_create(): void
    {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'opening_balance' => 100,
            'balance_type' => 'debit',
            'opening_date' => '2026-04-01',
        ]);

        $this->accountService->update($account->id, [
            'opening_balance' => 500,
            'balance_type' => 'credit',
            'opening_date' => '2026-05-01',
        ]);

        $fresh = $account->fresh();
        $this->assertSame(100.0, (float) $fresh->opening_balance);
        $this->assertSame('debit', $fresh->balance_type);
        $this->assertSame('2026-04-01', $fresh->opening_date->toDateString());
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

    public function test_cannot_delete_manual_account_with_posted_transactions_but_can_rename_it(): void
    {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_name' => 'Manual Expense',
            'account_type' => 'expense',
            'entry_source' => 'manual',
            'is_system' => false,
        ]);

        Ledger::factory()->create([
            'company_id' => $this->company->id,
            'financial_year_id' => $this->financialYear->id,
            'account_id' => $account->id,
            'voucher_id' => null,
            'reference_type' => 'manual_test',
            'reference_id' => $account->id,
            'debit' => 100,
            'credit' => 0,
        ]);

        $this->accountService->update($account->id, [
            'account_name' => 'Renamed Manual Expense',
            'account_type' => 'asset',
            'opening_balance' => 999,
        ]);

        $fresh = $account->fresh();
        $this->assertSame('Renamed Manual Expense', $fresh->account_name);
        $this->assertSame('expense', $fresh->account_type);
        $this->assertNotEquals(999.0, (float) $fresh->opening_balance);

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

    public function test_loading_account_master_does_not_reactivate_an_inactive_system_account(): void
    {
        $this->accountService->ensureDefaultLedgersAndCleanupDuplicates(
            $this->company->id,
            $this->financialYear->id
        );

        $account = Account::where('company_id', $this->company->id)
            ->where('account_code', Account::CODE_SUSPENSE)
            ->firstOrFail();

        $this->accountService->update($account->id, ['is_active' => false]);
        $this->accountService->ensureDefaultLedgersAndCleanupDuplicates(
            $this->company->id,
            $this->financialYear->id
        );

        $this->assertFalse($account->fresh()->is_active);
    }
}
