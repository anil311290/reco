<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Services\AccountService;
use App\Services\PartyService;
use App\Services\ReportService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TallyAccountingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_receipt_adjustment_and_reports_remain_balanced(): void
    {
        $company = Company::factory()->create();
        $financialYear = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
        ]);

        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'opening_balance' => 0,
            'balance_type' => 'debit',
        ]);

        $expense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'account_name' => 'Office Expense',
            'account_type' => 'expense',
            'opening_balance' => 0,
            'balance_type' => 'debit',
        ]);

        $income = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'account_name' => 'Other Income',
            'account_type' => 'income',
            'opening_balance' => 0,
            'balance_type' => 'credit',
        ]);

        /** @var PartyService $partyService */
        $partyService = app(PartyService::class);
        $creditor = $partyService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'name' => 'ABC Supplier',
            'type' => 'creditor',
            'opening_balance' => 0,
            'is_active' => true,
        ]);
        $debtor = $partyService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'name' => 'XYZ Customer',
            'type' => 'debtor',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        /** @var VoucherService $voucherService */
        $voucherService = app(VoucherService::class);
        $date = '2026-07-15';

        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'party_id' => $creditor->id,
            'voucher_type' => 'payment',
            'voucher_date' => $date,
            'narration' => 'Supplier payment',
            'lines' => [
                ['account_id' => $creditor->account_id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'party_id' => $debtor->id,
            'voucher_type' => 'receipt',
            'voucher_date' => $date,
            'narration' => 'Customer receipt',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 200, 'credit' => 0],
                ['account_id' => $debtor->account_id, 'debit' => 0, 'credit' => 200],
            ],
        ]);

        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'voucher_type' => 'journal',
            'voucher_date' => $date,
            'narration' => 'Adjustment',
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 50, 'credit' => 0],
                ['account_id' => $income->id, 'debit' => 0, 'credit' => 50],
            ],
        ]);

        /** @var ReportService $reportService */
        $reportService = app(ReportService::class);
        $dayBook = $reportService->getDayBook($company->id, $date);
        $this->assertEquals(350.0, (float) $dayBook['total_debit']);
        $this->assertEquals(350.0, (float) $dayBook['total_credit']);

        $trialBalance = app(\App\Services\LedgerService::class)
            ->getTrialBalance($company->id, $financialYear->id);
        $this->assertTrue($trialBalance['is_balanced']);
        $this->assertEquals(
            round((float) $trialBalance['total_debit'], 2),
            round((float) $trialBalance['total_credit'], 2)
        );

        // Inactivation prevents future selection but must not erase history.
        $expense->update(['is_active' => false]);
        $profitLoss = $reportService->getProfitLoss($company->id, $financialYear->id);
        $this->assertTrue(
            collect($profitLoss['expense']['accounts'])
                ->contains(fn (array $row) => $row['account']->id === $expense->id)
        );

        /** @var AccountService $accountService */
        $accountService = app(AccountService::class);
        // Particulars offer every party (debtor + creditor) plus ledger accounts,
        // like adjustments. Only the cash/bank/OD contra accounts are excluded.
        $paymentOptions = $accountService->getPaymentParticularsOptions($company->id, 'payment');
        $this->assertSame('Parties', $paymentOptions[0]['group'] ?? null);
        $this->assertTrue(collect($paymentOptions)->contains('party_id', $creditor->id));
        $this->assertTrue(collect($paymentOptions)->contains('party_id', $debtor->id));
        $this->assertTrue(collect($paymentOptions)->contains(
            fn (array $o) => ($o['kind'] ?? null) === 'account' && (int) $o['id'] === $income->id
        ));
        $this->assertFalse(collect($paymentOptions)->contains(
            fn (array $o) => ($o['kind'] ?? null) === 'account' && (int) $o['id'] === $cash->id
        ));

        $receiptOptions = $accountService->getPaymentParticularsOptions($company->id, 'receipt');
        $this->assertTrue(collect($receiptOptions)->contains('party_id', $debtor->id));
        $this->assertTrue(collect($receiptOptions)->contains('party_id', $creditor->id));
        $this->assertTrue(collect($receiptOptions)->contains(
            fn (array $o) => ($o['kind'] ?? null) === 'account' && (int) $o['id'] === $income->id
        ));
        $this->assertFalse(collect($receiptOptions)->contains(
            fn (array $o) => ($o['kind'] ?? null) === 'account' && (int) $o['id'] === $cash->id
        ));
    }
}
