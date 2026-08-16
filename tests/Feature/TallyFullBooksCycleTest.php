<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Voucher;
use App\Services\AccountService;
use App\Services\LedgerService;
use App\Services\PartyService;
use App\Services\PeriodLockService;
use App\Services\PurchaseInvoiceService;
use App\Services\ReportService;
use App\Services\SalesInvoiceService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Full Tally-style double-entry cycle:
 * Sales → Receipt (bill-wise) → Purchase → Payment (bill-wise) → Journal
 * then Day Book, Receipt & Payment, Trial Balance, P&L, Balance Sheet, Outstanding.
 */
class TallyFullBooksCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_tally_cycle_keeps_books_and_reports_balanced(): void
    {
        $date = '2026-07-16';

        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => 'CASH01',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'opening_balance' => 0,
            'balance_type' => 'debit',
            'is_active' => true,
        ]);

        $bank = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => 'BANK01',
            'account_name' => 'Bank',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'opening_balance' => 0,
            'balance_type' => 'debit',
            'is_active' => true,
        ]);

        $salesIncome = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => Account::CODE_AR_INCOME,
            'account_name' => 'Sales Revenue',
            'account_type' => 'income',
            'opening_balance' => 0,
            'balance_type' => 'credit',
            'is_system' => true,
            'is_active' => true,
        ]);

        $purchaseExpense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => Account::CODE_AP_EXPENSE,
            'account_name' => 'Purchase Expense',
            'account_type' => 'expense',
            'opening_balance' => 0,
            'balance_type' => 'debit',
            'is_system' => true,
            'is_active' => true,
        ]);

        $officeExpense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => 'EXP01',
            'account_name' => 'Office Expense',
            'account_type' => 'expense',
            'opening_balance' => 0,
            'balance_type' => 'debit',
            'is_active' => true,
        ]);

        $otherIncome = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => 'INC01',
            'account_name' => 'Other Income',
            'account_type' => 'income',
            'opening_balance' => 0,
            'balance_type' => 'credit',
            'is_active' => true,
        ]);

        Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => Account::CODE_AR,
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'opening_balance' => 0,
            'balance_type' => 'debit',
            'is_system' => true,
            'is_active' => true,
        ]);

        Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => Account::CODE_AP,
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'opening_balance' => 0,
            'balance_type' => 'credit',
            'is_system' => true,
            'is_active' => true,
        ]);

        /** @var PartyService $partyService */
        $partyService = app(PartyService::class);
        $customer = $partyService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'name' => 'Customer Alpha',
            'type' => 'debtor',
            'opening_balance' => 0,
            'is_active' => true,
        ]);
        $supplier = $partyService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'name' => 'Supplier Beta',
            'type' => 'creditor',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        /** @var SalesInvoiceService $salesService */
        $salesService = app(SalesInvoiceService::class);
        /** @var PurchaseInvoiceService $purchaseService */
        $purchaseService = app(PurchaseInvoiceService::class);
        /** @var VoucherService $voucherService */
        $voucherService = app(VoucherService::class);
        /** @var ReportService $reportService */
        $reportService = app(ReportService::class);
        /** @var LedgerService $ledgerService */
        $ledgerService = app(LedgerService::class);
        /** @var AccountService $accountService */
        $accountService = app(AccountService::class);

        // --- 1) Sales invoice 1000 (credit sale) ---
        $salesInvoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $customer->id,
            'invoice_number' => 'INV-000001',
            'invoice_date' => $date,
            'due_date' => '2026-07-30',
            'status' => 'draft',
            'discount_percentage' => 0,
        ], [
            [
                'account_id' => $salesIncome->id,
                'description' => 'Goods',
                'quantity' => 1,
                'unit_price' => 1000,
                'discount_percentage' => 0,
                'tax_amount' => 0,
            ],
        ]);
        $salesService->generateVoucher($salesInvoice);
        $salesInvoice->refresh();

        $this->assertEquals(1000.0, (float) $salesInvoice->total);
        $this->assertEquals(1000.0, (float) $salesInvoice->balance_due);
        $this->assertTrue(
            Voucher::where('sales_invoice_id', $salesInvoice->id)
                ->where('voucher_type', 'income')
                ->where('status', 'posted')
                ->exists()
        );

        // --- 2) Bill-wise receipt 600 against sales ---
        $salesInvoice = $salesService->recordPayment($salesInvoice->id, [
            'amount' => 600,
            'cash_bank_account_id' => $cash->id,
            'payment_date' => $date,
        ]);

        $this->assertEquals(600.0, (float) $salesInvoice->amount_paid);
        $this->assertEquals(400.0, (float) $salesInvoice->balance_due);
        $this->assertEquals('partial', $salesInvoice->status);
        $this->assertTrue(
            Voucher::where('sales_invoice_id', $salesInvoice->id)
                ->where('voucher_type', 'receipt')
                ->where('status', 'posted')
                ->exists()
        );

        // --- 3) Purchase invoice 400 ---
        $purchaseInvoice = $purchaseService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $supplier->id,
            'invoice_number' => 'PUR-000001',
            'invoice_date' => $date,
            'due_date' => '2026-07-30',
            'status' => 'draft',
            'discount_percentage' => 0,
        ], [
            [
                'account_id' => $purchaseExpense->id,
                'description' => 'Supplies',
                'quantity' => 1,
                'unit_price' => 400,
                'discount_percentage' => 0,
                'tax_amount' => 0,
            ],
        ]);
        $purchaseService->generateVoucher($purchaseInvoice);
        $purchaseInvoice->refresh();
        $this->assertEquals(400.0, (float) $purchaseInvoice->balance_due);

        // --- 4) Bill-wise payment 400 against purchase ---
        $purchaseInvoice = $purchaseService->recordPayment($purchaseInvoice->id, [
            'amount' => 400,
            'cash_bank_account_id' => $cash->id,
            'payment_date' => $date,
        ]);
        $this->assertEquals('paid', $purchaseInvoice->status);
        $this->assertEquals(0.0, (float) $purchaseInvoice->balance_due);

        // --- 5) Journal / Adjustment (Tally style lines) ---
        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'journal',
            'voucher_date' => $date,
            'narration' => 'Office expense adjustment',
            'lines' => [
                ['account_id' => $officeExpense->id, 'debit' => 50, 'credit' => 0],
                ['account_id' => $otherIncome->id, 'debit' => 0, 'credit' => 50],
            ],
        ]);

        // --- 6) Standalone receipt into bank from customer (advance / collection) ---
        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $customer->id,
            'voucher_type' => 'receipt',
            'voucher_date' => $date,
            'narration' => 'Additional customer collection',
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $customer->account_id, 'party_id' => $customer->id, 'debit' => 0, 'credit' => 100],
            ],
        ]);

        // Expected ledger positions after all postings:
        // Cash: +600 -400 = 200 Dr
        // Bank: +100 = 100 Dr
        // Customer: +1000 -600 -100 = 300 Dr
        // Supplier: +400 -400 = 0
        // Sales: 1000 Cr
        // Purchase: 400 Dr
        // Office: 50 Dr
        // Other Income: 50 Cr
        // TB Dr = 200+100+300+400+50 = 1050
        // TB Cr = 1000+50 = 1050

        $cashBal = $ledgerService->getAccountBalance($cash->id, $company->id, $fy->id);
        $this->assertEquals(200.0, (float) $cashBal['balance']);
        $this->assertEquals('debit', $cashBal['type']);

        $bankBal = $ledgerService->getAccountBalance($bank->id, $company->id, $fy->id);
        $this->assertEquals(100.0, (float) $bankBal['balance']);
        $this->assertEquals('debit', $bankBal['type']);

        $customerBal = $ledgerService->getAccountBalance((int) $customer->account_id, $company->id, $fy->id);
        $this->assertEquals(300.0, (float) $customerBal['balance']);
        $this->assertEquals('debit', $customerBal['type']);

        $supplierBal = $ledgerService->getAccountBalance((int) $supplier->account_id, $company->id, $fy->id);
        $this->assertEquals(0.0, (float) $supplierBal['balance']);

        // --- Day Book ---
        $dayBook = $reportService->getDayBook($company->id, $date);
        $this->assertGreaterThan(0, count($dayBook['rows']));
        $this->assertEqualsWithDelta(
            (float) $dayBook['total_debit'],
            (float) $dayBook['total_credit'],
            0.01,
            'Day Book must balance (Dr = Cr)'
        );
        $this->assertEqualsWithDelta(2550.0, (float) $dayBook['total_debit'], 0.01);
        // income 1000 + receipt 600 + expense 400 + payment 400 + journal 50 + receipt 100 = 2550 each side

        // --- Receipt & Payment ---
        // Receipts: 600 cash + 100 bank collected from the customer = 700
        // Payments: 400 cash paid to the supplier
        // Closing: cash 200 + bank 100 = 300
        $receiptPayment = $reportService->getReceiptPayment($company->id, null, null, $fy->id);
        $this->assertNull($receiptPayment['message']);
        $this->assertEqualsWithDelta(0.0, (float) $receiptPayment['opening_total'], 0.01);
        $this->assertEqualsWithDelta(700.0, (float) $receiptPayment['receipts']['total'], 0.01);
        $this->assertEqualsWithDelta(400.0, (float) $receiptPayment['payments']['total'], 0.01);
        $this->assertEqualsWithDelta(300.0, (float) $receiptPayment['closing_total'], 0.01);
        $this->assertTrue($receiptPayment['is_balanced']);

        $cashRow = collect($receiptPayment['accounts'])->first(
            fn (array $row) => (int) $row['account']->id === (int) $cash->id
        );
        $this->assertEqualsWithDelta(600.0, (float) $cashRow['received'], 0.01);
        $this->assertEqualsWithDelta(400.0, (float) $cashRow['paid'], 0.01);
        $this->assertEqualsWithDelta(200.0, (float) $cashRow['closing'], 0.01);

        $bankRow = collect($receiptPayment['accounts'])->first(
            fn (array $row) => (int) $row['account']->id === (int) $bank->id
        );
        $this->assertEqualsWithDelta(100.0, (float) $bankRow['received'], 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $bankRow['closing'], 0.01);

        // --- Trial Balance ---
        $trial = $ledgerService->getTrialBalance($company->id, $fy->id);
        $this->assertTrue($trial['is_balanced'], 'Trial Balance must be balanced');
        $this->assertEqualsWithDelta(
            (float) $trial['total_debit'],
            (float) $trial['total_credit'],
            0.01
        );
        $this->assertEqualsWithDelta(1050.0, (float) $trial['total_debit'], 0.01);

        // --- Profit & Loss ---
        // Income: Sales 1000 + Other 50 = 1050
        // Expense: Purchase 400 + Office 50 = 450
        // Net profit = 600
        $pl = $reportService->getProfitLoss($company->id, $fy->id);
        $this->assertEqualsWithDelta(1050.0, (float) $pl['income']['total'], 0.01);
        $this->assertEqualsWithDelta(450.0, (float) $pl['expense']['total'], 0.01);
        $this->assertEqualsWithDelta(600.0, (float) $pl['net_profit'], 0.01);
        $this->assertTrue($pl['is_profit']);

        // --- Balance Sheet ---
        // Assets: Cash 200 + Bank 100 + Debtor 300 = 600
        // Equity (net profit) = 600
        $bs = $reportService->getBalanceSheet($company->id, $fy->id);
        $this->assertTrue($bs['is_balanced'], 'Balance Sheet must balance');
        $this->assertEqualsWithDelta(600.0, (float) $bs['assets']['total'], 0.01);
        $this->assertEqualsWithDelta(
            (float) $bs['assets']['total'],
            (float) $bs['total_liabilities_equity'],
            0.01
        );
        $this->assertEqualsWithDelta(600.0, (float) $bs['equity']['net_profit'], 0.01);

        // --- Outstanding (invoice level) ---
        // Sales invoice balance_due = 400 (600 received bill-wise against it).
        // The standalone 100 advance credits the party ledger but is not applied
        // to the invoice, so invoice-level outstanding stays at 400.
        $debtors = $reportService->getDebtorsOutstanding($company->id);
        $this->assertEqualsWithDelta(400.0, (float) $debtors['total'], 0.01);
        $this->assertTrue(
            collect($debtors['debtors'])->contains(fn (array $row) => $row['party']->id === $customer->id)
        );

        $creditors = $reportService->getCreditorsOutstanding($company->id);
        $this->assertEqualsWithDelta(0.0, (float) $creditors['total'], 0.01);

        // --- Party ledger continuity ---
        $partyLedger = $ledgerService->getAccountLedger(
            (int) $customer->account_id,
            $company->id,
            $fy->id
        );
        $this->assertGreaterThanOrEqual(3, $partyLedger['entries']->count());
        $this->assertEqualsWithDelta(300.0, (float) $partyLedger['closing_balance']['balance'], 0.01);

        // --- Ledger entries exist for every posted voucher ---
        $postedVoucherIds = Voucher::where('company_id', $company->id)
            ->where('status', 'posted')
            ->pluck('id');
        $this->assertGreaterThanOrEqual(6, $postedVoucherIds->count());
        foreach ($postedVoucherIds as $voucherId) {
            $this->assertTrue(
                Ledger::where('voucher_id', $voucherId)->exists(),
                "Ledger entries missing for voucher {$voucherId}"
            );
        }

        // --- Particulars rules: all parties + ledger accounts (like adjustments),
        // excluding only the cash/bank/OD contra accounts. ---
        $paymentOptions = $accountService->getPaymentParticularsOptions($company->id, 'payment');
        $this->assertTrue(collect($paymentOptions)->contains('party_id', $supplier->id));
        $this->assertTrue(collect($paymentOptions)->contains('party_id', $customer->id));
        $this->assertTrue(collect($paymentOptions)->contains(
            fn (array $o) => $o['kind'] === 'account' && (int) $o['id'] === $officeExpense->id
        ));
        $this->assertFalse(collect($paymentOptions)->contains(
            fn (array $o) => $o['kind'] === 'account' && (int) $o['id'] === $cash->id
        ));
        $this->assertFalse(collect($paymentOptions)->contains(
            fn (array $o) => $o['kind'] === 'account' && (int) $o['id'] === $bank->id
        ));

        $receiptOptions = $accountService->getPaymentParticularsOptions($company->id, 'receipt');
        $this->assertTrue(collect($receiptOptions)->contains('party_id', $customer->id));
        $this->assertTrue(collect($receiptOptions)->contains('party_id', $supplier->id));
        $this->assertTrue(collect($receiptOptions)->contains(
            fn (array $o) => $o['kind'] === 'account' && (int) $o['id'] === $officeExpense->id
        ));

        $adjustmentOptions = $accountService->getAdjustmentParticularsOptions($company->id);
        $this->assertTrue(collect($adjustmentOptions)->contains('kind', 'party'));
        $this->assertTrue(collect($adjustmentOptions)->contains('kind', 'account'));

        // --- Cancel bill-wise receipt → outstanding restored; books stay balanced ---
        $receiptVoucher = Voucher::where('sales_invoice_id', $salesInvoice->id)
            ->where('voucher_type', 'receipt')
            ->where('status', 'posted')
            ->firstOrFail();
        $voucherService->cancel($receiptVoucher->id);

        $salesInvoice->refresh();
        $this->assertEquals(0.0, (float) $salesInvoice->amount_paid);
        $this->assertEquals(1000.0, (float) $salesInvoice->balance_due);
        $this->assertEquals('sent', $salesInvoice->status);

        $trialAfterCancel = $ledgerService->getTrialBalance($company->id, $fy->id);
        $this->assertTrue($trialAfterCancel['is_balanced'], 'TB must remain balanced after receipt cancel');

        $bsAfterCancel = $reportService->getBalanceSheet($company->id, $fy->id);
        $this->assertTrue($bsAfterCancel['is_balanced'], 'BS must remain balanced after receipt cancel');

        // --- Period lock: closed FY blocks new posting ---
        $fy->close();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is closed');
        app(PeriodLockService::class)->assertWritable($company->id, $date, $fy->id);
    }

    public function test_unbalanced_voucher_is_rejected(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
            'is_closed' => false,
        ]);
        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'opening_balance' => 0,
            'balance_type' => 'debit',
        ]);
        $expense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_type' => 'expense',
            'opening_balance' => 0,
            'balance_type' => 'debit',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('balanced');

        app(VoucherService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'payment',
            'voucher_date' => now()->toDateString(),
            'narration' => 'Bad voucher',
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 100, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 90],
            ],
        ]);
    }
}
