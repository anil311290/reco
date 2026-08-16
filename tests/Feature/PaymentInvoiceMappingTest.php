<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\PaymentInvoiceMapping;
use App\Models\Voucher;
use App\Services\PartyService;
use App\Services\PurchaseInvoiceService;
use App\Services\ReportService;
use App\Services\SalesInvoiceService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentInvoiceMappingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_invoice_payment_creates_payment_invoice_mapping(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor();

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-100001',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $salesService->generateVoucher($invoice);

        $invoice = $salesService->recordPayment($invoice->id, [
            'amount' => 40,
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-12',
        ]);

        $mapping = PaymentInvoiceMapping::where('invoice_type', 'sales')
            ->where('invoice_id', $invoice->id)
            ->first();

        $this->assertNotNull($mapping);
        $this->assertEquals(40.0, (float) $mapping->amount_allocated);
        $this->assertEquals('full', $mapping->status);
        $this->assertEquals(0.0, $mapping->getOutstandingAmount());

        $receipt = Voucher::where('sales_invoice_id', $invoice->id)
            ->where('voucher_type', 'receipt')
            ->first();
        $this->assertEquals($receipt->id, $mapping->payment_voucher_id);
    }

    public function test_purchase_invoice_payment_creates_payment_invoice_mapping(): void
    {
        [$company, $fy, $cash, $creditor] = $this->seedCompanyWithCashAndCreditor();

        $purchaseService = app(PurchaseInvoiceService::class);
        $invoice = $purchaseService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $creditor->id,
            'invoice_number' => 'PUR-100001',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Supplies', 'quantity' => 1, 'unit_price' => 200, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $purchaseService->generateVoucher($invoice);

        $invoice = $purchaseService->recordPayment($invoice->id, [
            'amount' => 200,
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-11',
        ]);

        $mapping = PaymentInvoiceMapping::where('invoice_type', 'purchase')
            ->where('invoice_id', $invoice->id)
            ->first();

        $this->assertNotNull($mapping);
        $this->assertEquals(200.0, (float) $mapping->amount_allocated);
        $this->assertEquals('full', $mapping->status);
    }

    public function test_cancelling_receipt_reverses_mapping_and_restores_invoice_balance(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor();

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-100002',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $salesService->generateVoucher($invoice);
        $invoice = $salesService->recordPayment($invoice->id, [
            'amount' => 40,
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-12',
        ]);

        $receipt = Voucher::where('sales_invoice_id', $invoice->id)
            ->where('voucher_type', 'receipt')
            ->firstOrFail();

        app(VoucherService::class)->cancel($receipt->id);

        $mapping = PaymentInvoiceMapping::where('payment_voucher_id', $receipt->id)->first();
        $this->assertEquals('reversed', $mapping->status);

        $invoice->refresh();
        $this->assertEquals(0.0, (float) $invoice->amount_paid);
        $this->assertEquals(100.0, (float) $invoice->balance_due);
    }

    public function test_report_service_returns_invoice_and_payment_settlement_details(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor();

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-100003',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Widget', 'quantity' => 1, 'unit_price' => 100, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $salesService->generateVoucher($invoice);
        $invoice = $salesService->recordPayment($invoice->id, [
            'amount' => 100,
            'cash_bank_account_id' => $cash->id,
            'payment_date' => '2026-07-12',
        ]);

        $receipt = Voucher::where('sales_invoice_id', $invoice->id)
            ->where('voucher_type', 'receipt')
            ->firstOrFail();

        $reportService = app(ReportService::class);

        $invoiceDetails = $reportService->getInvoiceSettlementDetails('sales', $invoice->id);
        $this->assertEquals(100.0, $invoiceDetails['total_settled']);
        $this->assertCount(1, $invoiceDetails['settlements']);

        $paymentDetails = $reportService->getPaymentSettlementDetails($receipt->id);
        $this->assertEquals(100.0, $paymentDetails['total_settled']);
        $this->assertCount(1, $paymentDetails['invoices_settled']);

        $audit = $reportService->getSettlementAuditReport($company->id);
        $this->assertEquals(1, $audit['summary']['total_mappings']);
        $this->assertEquals(100.0, $audit['summary']['total_settled']);
    }

    public function test_multi_invoice_payment_splits_one_receipt_across_two_sales_invoices(): void
    {
        [$company, $fy, $cash, $debtor] = $this->seedCompanyWithCashAndDebtor();

        $salesService = app(SalesInvoiceService::class);
        $invoiceOne = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-200001',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Widget A', 'quantity' => 1, 'unit_price' => 100, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $salesService->generateVoucher($invoiceOne);

        $invoiceTwo = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-200002',
            'invoice_date' => '2026-07-11',
            'due_date' => '2026-07-21',
            'status' => 'draft',
        ], [
            ['description' => 'Widget B', 'quantity' => 1, 'unit_price' => 50, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $salesService->generateVoucher($invoiceTwo);

        $result = $salesService->recordMultiInvoicePayment(
            $debtor->id,
            [
                ['invoice_id' => $invoiceOne->id, 'amount' => 60],
                ['invoice_id' => $invoiceTwo->id, 'amount' => 50],
            ],
            $cash->id,
            '2026-07-12',
            ['company_id' => $company->id]
        );

        $this->assertEquals(110.0, (float) $result['voucher']->total_debit);
        $this->assertCount(2, $result['invoices']);

        $invoiceOne->refresh();
        $invoiceTwo->refresh();
        $this->assertEquals(40.0, (float) $invoiceOne->balance_due);
        $this->assertEquals('partial', $invoiceOne->status);
        $this->assertEquals(0.0, (float) $invoiceTwo->balance_due);
        $this->assertEquals('paid', $invoiceTwo->status);

        $mappings = PaymentInvoiceMapping::where('payment_voucher_id', $result['voucher']->id)->get();
        $this->assertCount(2, $mappings);
        $this->assertEquals(110.0, (float) $mappings->sum('amount_settled'));
    }

    private function seedCompanyWithCashAndDebtor(): array
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
            'is_cash_bank_od' => true,
            'opening_balance' => 0,
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

    private function seedCompanyWithCashAndCreditor(): array
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
            'is_cash_bank_od' => true,
            'opening_balance' => 500,
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
