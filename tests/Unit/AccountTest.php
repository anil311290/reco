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
        ]);

        $this->assertStringStartsWith('AST', $account->account_code);
    }

    public function test_can_create_account_with_parent(): void
    {
        $parent = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => 'asset',
        ]);

        $child = $this->accountService->create([
            'company_id' => $this->company->id,
            'account_name' => 'Savings Account',
            'account_type' => 'asset',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertTrue($parent->fresh()->hasChildren());
    }

    public function test_can_update_account(): void
    {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $updated = $this->accountService->update($account->id, [
            'account_name' => 'Updated Account',
        ]);

        $this->assertTrue($updated);
        $this->assertEquals('Updated Account', $account->fresh()->account_name);
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

    public function test_cannot_delete_account_with_children(): void
    {
        $parent = Account::factory()->create([
            'company_id' => $this->company->id,
            'is_system' => false,
        ]);

        Account::factory()->create([
            'company_id' => $this->company->id,
            'parent_id' => $parent->id,
        ]);

        $this->expectException(\Exception::class);
        $this->accountService->delete($parent->id);
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

    public function test_can_get_account_tree(): void
    {
        $parent = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => 'asset',
            'parent_id' => null,
        ]);

        $child = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => 'asset',
            'parent_id' => $parent->id,
        ]);

        $tree = $this->accountService->getTree($this->company->id, 'asset');

        $this->assertNotEmpty($tree);
    }

    public function test_account_type_label(): void
    {
        $account = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_type' => 'asset',
        ]);

        $this->assertEquals('Asset', $account->type_label);
    }

    public function test_account_full_path(): void
    {
        $grandparent = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_name' => 'Assets',
        ]);

        $parent = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_name' => 'Current Assets',
            'parent_id' => $grandparent->id,
        ]);

        $child = Account::factory()->create([
            'company_id' => $this->company->id,
            'account_name' => 'Cash',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals('Assets > Current Assets > Cash', $child->full_path);
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
