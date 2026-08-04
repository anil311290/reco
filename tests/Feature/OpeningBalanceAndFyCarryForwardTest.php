<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Voucher;
use App\Services\AccountService;
use App\Services\FinancialYearService;
use App\Services\LedgerService;
use App\Services\ReportService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OpeningBalanceAndFyCarryForwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_opening_balance_posts_balanced_suspense_entries(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
            'is_closed' => false,
        ]);

        $account = app(AccountService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_name' => 'Petty Cash',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'opening_balance' => 500,
            'balance_type' => 'debit',
            'is_active' => true,
        ]);

        $openingVoucher = Voucher::where('company_id', $company->id)
            ->where('narration', 'like', "[OB:account:{$account->id}]%")
            ->firstOrFail();

        $this->assertTrue(Ledger::where('account_id', $account->id)
            ->where('voucher_id', $openingVoucher->id)
            ->exists());

        $this->assertTrue(Ledger::where('voucher_id', $openingVoucher->id)->where('is_opening_balance', true)->exists());

        $normalVoucher = app(VoucherService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'journal',
            'voucher_date' => '2026-07-15',
            'narration' => 'Normal journal',
            'lines' => [
                ['account_id' => $account->id, 'debit' => 10, 'credit' => 0],
                ['account_id' => $account->id, 'debit' => 0, 'credit' => 10],
            ],
        ]);

        $this->assertTrue(Ledger::where('voucher_id', $normalVoucher->id)->where('is_opening_balance', false)->exists());

        $trial = app(LedgerService::class)->getTrialBalance($company->id, $fy->id);
        $this->assertTrue($trial['is_balanced']);
        $this->assertEqualsWithDelta(
            (float) $trial['total_debit'],
            (float) $trial['total_credit'],
            0.01
        );
    }

    public function test_setting_new_fy_current_carries_forward_opening_journal(): void
    {
        $company = Company::factory()->create();
        $fy1 = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'name' => '2025-2026',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);
        $fy2 = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'name' => '2026-2027',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);

        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy1->id,
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'opening_balance' => 0,
            'balance_type' => 'debit',
        ]);
        $income = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy1->id,
            'account_name' => 'Sales',
            'account_type' => 'income',
            'opening_balance' => 0,
            'balance_type' => 'credit',
        ]);

        app(VoucherService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy1->id,
            'voucher_type' => 'journal',
            'voucher_date' => '2025-06-15',
            'narration' => 'Seed FY1',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1000, 'credit' => 0],
                ['account_id' => $income->id, 'debit' => 0, 'credit' => 1000],
            ],
        ]);

        $fy1->close();
        app(FinancialYearService::class)->setAsCurrent($fy2);

        $opening = Voucher::query()
            ->where('financial_year_id', $fy2->id)
            ->where('voucher_type', 'journal')
            ->where('narration', 'like', 'Opening balances carried forward%')
            ->where('status', 'posted')
            ->first();

        $this->assertNotNull($opening);
        $this->assertTrue(Ledger::where('voucher_id', $opening->id)->where('is_opening_balance', true)->exists());
        $this->assertTrue($fy2->fresh()->is_current);

        $cashBal = app(LedgerService::class)->getAccountBalance($cash->id, $company->id, $fy2->id);
        $this->assertEqualsWithDelta(1000.0, (float) $cashBal['balance'], 0.01);
        $this->assertEquals('debit', $cashBal['type']);

        $trial = app(LedgerService::class)->getTrialBalance($company->id, $fy2->id);
        $this->assertTrue($trial['is_balanced']);
    }

    public function test_day_book_filters_by_financial_year(): void
    {
        $company = Company::factory()->create();
        $fy1 = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_current' => false,
            'is_closed' => false,
        ]);
        $fy2 = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy2->id,
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'opening_balance' => 0,
            'balance_type' => 'debit',
        ]);
        $expense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy2->id,
            'account_type' => 'expense',
            'opening_balance' => 0,
            'balance_type' => 'debit',
        ]);

        // Same calendar date exists in both FYs only if date is in range - use dates in each FY
        app(VoucherService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy1->id,
            'voucher_type' => 'payment',
            'voucher_date' => '2025-07-16',
            'narration' => 'FY1 payment',
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 10, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 10],
            ],
        ]);
        app(VoucherService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy2->id,
            'voucher_type' => 'payment',
            'voucher_date' => '2026-07-16',
            'narration' => 'FY2 payment',
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 20, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 20],
            ],
        ]);

        $dayFy2 = app(ReportService::class)->getDayBook($company->id, '2026-07-16', $fy2->id);
        $this->assertEqualsWithDelta(20.0, (float) $dayFy2['total_debit'], 0.01);
        $this->assertCount(1, $dayFy2['vouchers']);
    }
}
