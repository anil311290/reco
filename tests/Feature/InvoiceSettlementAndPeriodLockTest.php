<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Voucher;
use App\Services\PartyService;
use App\Services\PeriodLockService;
use App\Services\PurchaseInvoiceService;
use App\Services\SalesInvoiceService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceSettlementAndPeriodLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_record_payment_posts_receipt_voucher_bill_wise(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor(openingCash: 0);

        /** @var SalesInvoiceService $salesService */
        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-000001',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            [
                'description' => 'Widget',
                'quantity' => 1,
                'unit_price' => 100,
                'discount_percentage' => 0,
                'tax_amount' => 0,
            ],
        ]);
        $salesService->generateVoucher($invoice);
        $invoice->refresh();

        $this->assertEquals(100.0, (float) $invoice->balance_due);

        $invoice = $salesService->recordPayment($invoice->id, [
            'amount' => 40,
            'payment_mode' => 'cash',
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-12',
        ]);

        $this->assertEquals(40.0, (float) $invoice->amount_paid);
        $this->assertEquals(60.0, (float) $invoice->balance_due);
        $this->assertEquals('partial', $invoice->status);

        $receipt = Voucher::query()
            ->where('sales_invoice_id', $invoice->id)
            ->where('voucher_type', 'receipt')
            ->where('status', 'posted')
            ->first();

        $this->assertNotNull($receipt);
        $this->assertEquals(40.0, (float) $receipt->total_debit);
        $this->assertEquals(40.0, (float) $receipt->total_credit);
        $this->assertTrue(
            $receipt->lines->contains(fn ($line) => (int) $line->account_id === (int) $cash->id && (float) $line->debit === 40.0)
        );
        $this->assertTrue(
            $receipt->lines->contains(fn ($line) => (int) $line->account_id === (int) $debtor->account_id && (float) $line->credit === 40.0)
        );
    }

    public function test_purchase_invoice_record_payment_posts_payment_voucher_bill_wise(): void
    {
        [$company, $fy, $cash, $creditor] = $this->seedCompanyWithCashAndCreditor(openingCash: 500);

        /** @var PurchaseInvoiceService $purchaseService */
        $purchaseService = app(PurchaseInvoiceService::class);
        $invoice = $purchaseService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $creditor->id,
            'invoice_number' => 'PUR-000001',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            [
                'description' => 'Supplies',
                'quantity' => 1,
                'unit_price' => 200,
                'discount_percentage' => 0,
                'tax_amount' => 0,
            ],
        ]);
        $purchaseService->generateVoucher($invoice);
        $invoice->refresh();

        $invoice = $purchaseService->recordPayment($invoice->id, [
            'amount' => 200,
            'payment_mode' => 'cash',
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-11',
        ]);

        $this->assertEquals(200.0, (float) $invoice->amount_paid);
        $this->assertEquals(0.0, (float) $invoice->balance_due);
        $this->assertEquals('paid', $invoice->status);

        $payment = Voucher::query()
            ->where('purchase_invoice_id', $invoice->id)
            ->where('voucher_type', 'payment')
            ->where('status', 'posted')
            ->first();

        $this->assertNotNull($payment);
        $this->assertEquals(200.0, (float) $payment->total_debit);
        $this->assertTrue(
            $payment->lines->contains(fn ($line) => (int) $line->account_id === (int) $creditor->account_id && (float) $line->debit === 200.0)
        );
        $this->assertTrue(
            $payment->lines->contains(fn ($line) => (int) $line->account_id === (int) $cash->id && (float) $line->credit === 200.0)
        );
    }

    public function test_closed_financial_year_blocks_voucher_create(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
            'is_closed' => true,
            'closed_at' => now(),
        ]);

        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_type' => 'asset',
            'transaction_mode' => 'cash',
            'opening_balance' => 1000,
            'balance_type' => 'debit',
        ]);
        $expense = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_type' => 'expense',
            'opening_balance' => 0,
            'balance_type' => 'debit',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('is closed');

        app(VoucherService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'payment',
            'voucher_date' => '2026-07-15',
            'narration' => 'Should fail',
            'lines' => [
                ['account_id' => $expense->id, 'debit' => 10, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 10],
            ],
        ]);
    }

    public function test_period_lock_rejects_date_outside_financial_year(): void
    {
        $company = Company::factory()->create();
        FinancialYear::factory()->create([
            'company_id' => $company->id,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No financial year covers');

        app(PeriodLockService::class)->assertWritable($company->id, '2025-01-01');
    }

    public function test_cannot_double_cancel_settlement_voucher(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor(0);

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-000010',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 100, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $salesService->generateVoucher($invoice);
        $salesService->recordPayment($invoice->id, [
            'amount' => 100,
            'payment_mode' => 'cash',
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-11',
        ]);

        $receipt = Voucher::where('sales_invoice_id', $invoice->id)
            ->where('voucher_type', 'receipt')
            ->where('status', 'posted')
            ->firstOrFail();

        app(VoucherService::class)->cancel($receipt->id);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Only posted vouchers can be cancelled');
        app(VoucherService::class)->cancel($receipt->id);
    }

    public function test_cannot_cancel_invoice_income_voucher_directly(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor(0);

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-000011',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 50, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $salesService->generateVoucher($invoice);

        $income = Voucher::where('sales_invoice_id', $invoice->id)
            ->where('voucher_type', 'income')
            ->where('status', 'posted')
            ->firstOrFail();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invoice posting vouchers cannot be cancelled');
        app(VoucherService::class)->cancel($income->id);
    }

    public function test_generate_voucher_is_idempotent(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor(0);

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-000012',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 75, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);

        $first = $salesService->generateVoucher($invoice);
        $second = $salesService->generateVoucher($invoice->fresh());

        $this->assertEquals($first->id, $second->id);
        $this->assertEquals(1, Voucher::where('sales_invoice_id', $invoice->id)->where('voucher_type', 'income')->count());
    }

    public function test_cannot_pay_draft_invoice_without_posted_voucher(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor(0);

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-000013',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Item', 'quantity' => 1, 'unit_price' => 80, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Post the sales invoice');
        $salesService->recordPayment($invoice->id, [
            'amount' => 80,
            'payment_mode' => 'cash',
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-11',
        ]);
    }

    public function test_sales_invoice_cancel_reverses_receipt_income_and_marks_cancelled(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor(0);

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-000014',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'notes' => 'Delivery against PO-9',
            'status' => 'draft',
        ], [
            ['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);

        $voucher = $salesService->generateVoucher($invoice);
        $this->assertEquals('Delivery against PO-9', $voucher->narration);

        $salesService->recordPayment($invoice->id, [
            'amount' => 40,
            'payment_mode' => 'cash',
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-12',
        ]);

        $cancelled = $salesService->cancel($invoice->id);

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals(0.0, (float) $cancelled->amount_paid);
        $this->assertEquals(0.0, (float) $cancelled->balance_due);

        $this->assertEquals(0, Voucher::where('sales_invoice_id', $invoice->id)->where('status', 'posted')->count());
        $this->assertGreaterThan(0, Voucher::where('sales_invoice_id', $invoice->id)->where('status', 'cancelled')->count());
    }

    public function test_purchase_invoice_cancel_reverses_payment_expense_and_marks_cancelled(): void
    {
        [$company, $fy, $cash, $creditor] = $this->seedCompanyWithCashAndCreditor(500);

        $purchaseService = app(PurchaseInvoiceService::class);
        $invoice = $purchaseService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $creditor->id,
            'invoice_number' => 'PUR-000014',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Supplies', 'quantity' => 1, 'unit_price' => 200, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);

        $purchaseService->generateVoucher($invoice);
        $purchaseService->recordPayment($invoice->id, [
            'amount' => 50,
            'payment_mode' => 'cash',
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-12',
        ]);

        $cancelled = $purchaseService->cancel($invoice->id);

        $this->assertEquals('cancelled', $cancelled->status);
        $this->assertEquals(0.0, (float) $cancelled->amount_paid);
        $this->assertEquals(0.0, (float) $cancelled->balance_due);
        $this->assertEquals(0, Voucher::where('purchase_invoice_id', $invoice->id)->where('status', 'posted')->count());
    }

    private function seedCompanyWithCashAndDebtor(float $openingCash): array
    {
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
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'transaction_mode' => 'cash',
            'opening_balance' => $openingCash,
            'balance_type' => 'debit',
        ]);
        $debtor = app(PartyService::class)->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'name' => 'Customer A',
            'type' => 'debtor',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        return [$company, $fy, $cash, $debtor];
    }

    private function seedCompanyWithCashAndCreditor(float $openingCash): array
    {
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
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'transaction_mode' => 'cash',
            'opening_balance' => $openingCash,
            'balance_type' => 'debit',
        ]);
        $creditor = app(PartyService::class)->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'name' => 'Supplier A',
            'type' => 'creditor',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        return [$company, $fy, $cash, $creditor];
    }
}
