<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\TaxRate;
use App\Services\PurchaseInvoiceService;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceAccountingPostingTest extends TestCase
{
    use RefreshDatabase;

    protected function seedTaxRate(int $companyId): TaxRate
    {
        $taxRateId = DB::table('tax_rates')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,
            'name' => 'GST 18%',
            'code' => 'GST18',
            'rate' => 18,
            'type' => 'gst',
            'category' => 'GST',
            'calculation_type' => 'addition',
            'is_active' => true,
            'tax_name' => 'GST 18%',
            'tax_code' => 'GST18',
            'tax_rate' => 18,
            'tax_type' => 'addition',
            'tax_category' => 'GST',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return TaxRate::findOrFail($taxRateId);
    }

    protected function seedCoreAccounts(Company $company, FinancialYear $fy): array
    {
        $ar = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => Account::CODE_AR,
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'balance_type' => 'debit',
            'is_system' => true,
        ]);

        $sales = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => Account::CODE_AR_INCOME,
            'account_name' => 'Sales Revenue',
            'account_type' => 'income',
            'balance_type' => 'credit',
            'is_system' => true,
        ]);

        $serviceRevenue = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => Account::CODE_SERVICE_INCOME,
            'account_name' => 'Service Revenue',
            'account_type' => 'income',
            'balance_type' => 'credit',
            'is_system' => true,
        ]);

        $ap = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => Account::CODE_AP,
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'balance_type' => 'credit',
            'is_system' => true,
        ]);

        $purchase = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => Account::CODE_AP_EXPENSE,
            'account_name' => 'Purchase Expense',
            'account_type' => 'expense',
            'balance_type' => 'debit',
            'is_system' => true,
        ]);

        return compact('ar', 'sales', 'serviceRevenue', 'ap', 'purchase');
    }

    public function test_sales_invoice_number_counts_legacy_and_deleted_numbers(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $invoice = SalesInvoice::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => null,
            'invoice_number' => 'INV-202627/0001',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-31',
            'status' => 'draft',
        ]);
        $invoice->delete();

        $nextNumber = app(SalesInvoiceService::class)->generateInvoiceNumber(
            $company->id,
            $fy->id
        );

        $this->assertSame('INV-202627/0002', $nextNumber);
    }

    public function test_purchase_invoice_number_counts_legacy_and_deleted_numbers(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
        ]);

        $invoice = PurchaseInvoice::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => null,
            'invoice_number' => 'PUR-202627/0001',
            'invoice_date' => '2026-07-01',
            'due_date' => '2026-07-31',
            'status' => 'draft',
        ]);
        $invoice->delete();

        $nextNumber = app(PurchaseInvoiceService::class)->generateInvoiceNumber(
            $company->id,
            $fy->id
        );

        $this->assertSame('PUR-202627/0002', $nextNumber);
    }

    public function test_item_sales_with_header_discount_posts_balanced_entries(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create(['company_id' => $company->id, 'is_current' => true]);
        $this->seedCoreAccounts($company, $fy);
        $taxRate = $this->seedTaxRate($company->id);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'type' => 'debtor',
        ]);

        /** @var SalesInvoiceService $service */
        $service = app(SalesInvoiceService::class);

        $invoice = $service->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $party->id,
            'invoice_number' => 'INV-000001',
            'invoice_date' => '2026-07-06',
            'due_date' => '2026-07-13',
            'discount_percentage' => 10,
            'status' => 'draft',
        ], [
            [
                'description' => 'Goods',
                'quantity' => 2,
                'unit_price' => 100,
                'discount_percentage' => 0,
                'tax_rate_id' => $taxRate->id,
            ],
        ]);

        $voucher = $service->generateVoucher($invoice->fresh());
        $this->assertNotNull($voucher);

        $invoice = $invoice->fresh();
        $voucher = $voucher->fresh('lines');

        $this->assertEquals(216.0, (float) $invoice->total); // 200 - 10% header discount + 36 GST

        $debit = round((float) $voucher->lines->sum('debit'), 2);
        $credit = round((float) $voucher->lines->sum('credit'), 2);
        $this->assertEquals($debit, $credit);
        $this->assertEquals((float) $invoice->total, $debit);
    }

    public function test_sales_invoice_with_service_lines_posts_balanced_journal(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create(['company_id' => $company->id, 'is_current' => true]);
        $accounts = $this->seedCoreAccounts($company, $fy);
        $taxRate = $this->seedTaxRate($company->id);

        $serviceIncome = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_name' => 'Consulting Income',
            'account_type' => 'income',
            'balance_type' => 'credit',
        ]);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'type' => 'debtor',
        ]);

        /** @var SalesInvoiceService $service */
        $service = app(SalesInvoiceService::class);

        $invoice = $service->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $party->id,
            'invoice_number' => 'INV-202627/0001',
            'invoice_date' => '2026-07-06',
            'due_date' => '2026-07-13',
            'status' => 'draft',
        ], [], [
            [
                'account_id' => $serviceIncome->id,
                'tax_rate_id' => $taxRate->id,
                'description' => 'Consulting',
                'amount' => 1000,
            ],
        ]);

        $voucher = $service->generateVoucher($invoice->fresh());
        $this->assertNotNull($voucher);

        $ledgerRows = Ledger::where('company_id', $company->id)
            ->where('voucher_id', $voucher->id)
            ->get();

        $this->assertGreaterThan(0, $ledgerRows->count());
        $this->assertEquals(
            round((float) $ledgerRows->sum('debit'), 2),
            round((float) $ledgerRows->sum('credit'), 2)
        );

        $incomeLine = $voucher->fresh('lines.account')->lines->first(
            fn ($line) => (int) $line->account_id === (int) $serviceIncome->id
        );
        $this->assertNotNull($incomeLine);
        $this->assertEquals(1000.0, (float) $incomeLine->credit);

        $originalVoucherId = $voucher->id;
        $service->updateWithLines($invoice->id, [
            'notes' => 'Altered service invoice',
        ], [], [
            [
                'account_id' => $serviceIncome->id,
                'tax_rate_id' => $taxRate->id,
                'description' => 'Consulting revised',
                'amount' => 2000,
            ],
        ]);

        $alteredVoucher = $invoice->fresh()->vouchers()->where('status', 'posted')->firstOrFail();
        $alteredVoucher->load('lines');

        $this->assertSame($originalVoucherId, $alteredVoucher->id);
        $this->assertEquals(2360.0, round((float) $alteredVoucher->lines->sum('debit'), 2));
        $this->assertEquals(
            round((float) $alteredVoucher->lines->sum('debit'), 2),
            round((float) $alteredVoucher->lines->sum('credit'), 2)
        );
    }

    public function test_sales_invoice_with_service_item_line_posts_balanced_income(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create(['company_id' => $company->id, 'is_current' => true]);
        $accounts = $this->seedCoreAccounts($company, $fy);
        $taxRate = $this->seedTaxRate($company->id);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'type' => 'debtor',
        ]);

        /** @var \App\Services\ItemService $itemService */
        $itemService = app(\App\Services\ItemService::class);

        $serviceItem = $itemService->create([
            'company_id' => $company->id,
            'item_code' => 'SVC-CONSULT',
            'name' => 'Consulting Hours',
            'type' => 'service',
            'selling_price' => 500,
            'unit' => 'hrs',
            'tax_rate_id' => $taxRate->id,
            'is_active' => true,
        ]);

        $this->assertFalse((bool) $serviceItem->is_stockable);
        $this->assertSame('service', $serviceItem->type);
        $this->assertEquals(0.0, (float) $serviceItem->current_stock);
        $this->assertEquals((int) $accounts['serviceRevenue']->id, (int) $serviceItem->income_account_id);

        /** @var SalesInvoiceService $service */
        $service = app(SalesInvoiceService::class);

        $invoice = $service->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $party->id,
            'invoice_number' => 'INV-202627/0099',
            'invoice_date' => '2026-07-06',
            'due_date' => '2026-07-13',
            'status' => 'draft',
        ], [
            [
                'item_id' => $serviceItem->id,
                'description' => 'Consulting Hours',
                'quantity' => 2,
                'unit_price' => 500,
                'discount_percentage' => 0,
                'tax_rate_id' => $taxRate->id,
            ],
        ]);

        $line = $invoice->lines->first();
        $this->assertNotNull($line);
        $this->assertSame('service', $line->line_type);
        $this->assertEquals((int) $serviceItem->id, (int) $line->item_id);
        $this->assertEquals((int) $accounts['serviceRevenue']->id, (int) $line->account_id);

        $voucher = $service->generateVoucher($invoice->fresh());
        $this->assertNotNull($voucher);

        $voucher->load('lines');
        $debit = round((float) $voucher->lines->sum('debit'), 2);
        $credit = round((float) $voucher->lines->sum('credit'), 2);
        $this->assertEquals($debit, $credit);
        $this->assertEquals(1180.0, (float) $invoice->fresh()->total);

        $incomeLine = $voucher->lines->first(
            fn ($voucherLine) => (int) $voucherLine->account_id === (int) $accounts['serviceRevenue']->id
        );
        $this->assertNotNull($incomeLine);
        $this->assertEquals(1000.0, (float) $incomeLine->credit);

        $this->assertEquals(0.0, (float) $serviceItem->fresh()->current_stock);
    }

    public function test_mixed_sales_invoice_splits_goods_and_service_taxable_amounts(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create(['company_id' => $company->id, 'is_current' => true]);
        $accounts = $this->seedCoreAccounts($company, $fy);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'type' => 'debtor',
        ]);

        $goods = Item::create([
            'company_id' => $company->id,
            'name' => 'Product',
            'type' => 'goods',
            'selling_price' => 100,
            'unit' => 'pcs',
            'is_active' => true,
        ]);
        $serviceItem = Item::create([
            'company_id' => $company->id,
            'name' => 'Installation',
            'type' => 'service',
            'selling_price' => 200,
            'unit' => 'job',
            'is_active' => true,
        ]);

        $this->assertSame((int) $accounts['sales']->id, (int) $goods->income_account_id);
        $this->assertSame((int) $accounts['serviceRevenue']->id, (int) $serviceItem->income_account_id);

        $invoice = app(SalesInvoiceService::class)->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $party->id,
            'invoice_number' => 'INV-MIXED-001',
            'invoice_date' => '2026-07-30',
            'due_date' => '2026-08-06',
            'status' => 'draft',
        ], [
            [
                'item_id' => $goods->id,
                'description' => 'Product',
                'quantity' => 1,
                'unit_price' => 100,
            ],
            [
                'item_id' => $serviceItem->id,
                'description' => 'Installation',
                'quantity' => 1,
                'unit_price' => 200,
            ],
        ]);

        $voucher = app(SalesInvoiceService::class)->generateVoucher($invoice->fresh());
        $voucher->load('lines');

        $goodsCredit = $voucher->lines->firstWhere('account_id', $accounts['sales']->id);
        $serviceCredit = $voucher->lines->firstWhere('account_id', $accounts['serviceRevenue']->id);

        $this->assertNotNull($goodsCredit);
        $this->assertNotNull($serviceCredit);
        $this->assertSame(100.0, (float) $goodsCredit->credit);
        $this->assertSame(200.0, (float) $serviceCredit->credit);
        $this->assertSame(300.0, (float) $voucher->lines->sum('debit'));
        $this->assertSame(300.0, (float) $voucher->lines->sum('credit'));
    }

    public function test_purchase_invoice_posts_balanced_entries(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create(['company_id' => $company->id, 'is_current' => true]);
        $this->seedCoreAccounts($company, $fy);
        $taxRate = $this->seedTaxRate($company->id);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'type' => 'creditor',
        ]);

        /** @var PurchaseInvoiceService $service */
        $service = app(PurchaseInvoiceService::class);

        $invoice = $service->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $party->id,
            'invoice_number' => 'PUR-000001',
            'invoice_date' => '2026-07-06',
            'due_date' => '2026-07-13',
            'discount_percentage' => 0,
            'status' => 'draft',
        ], [
            [
                'description' => 'Office supplies',
                'quantity' => 1,
                'unit_price' => 500,
                'discount_percentage' => 0,
                'tax_rate_id' => $taxRate->id,
            ],
        ]);

        $voucher = $service->generateVoucher($invoice->fresh());
        $this->assertNotNull($voucher);

        $invoice = $invoice->fresh();
        $voucher = $voucher->fresh('lines');

        $this->assertSame('verified', $invoice->status);

        $debit = round((float) $voucher->lines->sum('debit'), 2);
        $credit = round((float) $voucher->lines->sum('credit'), 2);
        $this->assertEquals($debit, $credit);
        $this->assertEquals(590.0, (float) $invoice->total);
        $this->assertEquals(590.0, $debit);
    }

    public function test_sales_invoice_with_cash_linked_party_posts_debit_to_cash_ledger(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create(['company_id' => $company->id, 'is_current' => true]);
        $accounts = $this->seedCoreAccounts($company, $fy);

        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => 'CASH01',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'balance_type' => 'debit',
            'is_cash_bank_od' => true,
            'is_active' => true,
        ]);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'type' => 'debtor',
            'account_id' => $cash->id,
        ]);

        $invoice = app(SalesInvoiceService::class)->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $party->id,
            'invoice_number' => 'INV-CASH-001',
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-08',
            'status' => 'draft',
        ], [
            [
                'description' => 'Cash sale',
                'quantity' => 1,
                'unit_price' => 100,
            ],
        ]);

        $voucher = app(SalesInvoiceService::class)->generateVoucher($invoice->fresh());
        $this->assertNotNull($voucher);

        $voucher->load('lines');

        $cashDebit = $voucher->lines->firstWhere('account_id', $cash->id);
        $this->assertNotNull($cashDebit);
        $this->assertEquals(100.0, (float) $cashDebit->debit);

        $arDebit = $voucher->lines->firstWhere('account_id', $accounts['ar']->id);
        $this->assertNull($arDebit);
    }

    public function test_purchase_invoice_with_cash_linked_party_posts_credit_to_cash_ledger(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create(['company_id' => $company->id, 'is_current' => true]);
        $accounts = $this->seedCoreAccounts($company, $fy);

        $cash = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'account_code' => 'CASH02',
            'account_name' => 'Petty Cash',
            'account_type' => 'asset',
            'balance_type' => 'debit',
            'is_cash_bank_od' => true,
            'is_active' => true,
        ]);

        $party = Party::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'type' => 'creditor',
            'account_id' => $cash->id,
        ]);

        $invoice = app(PurchaseInvoiceService::class)->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $party->id,
            'invoice_number' => 'PUR-CASH-001',
            'invoice_date' => '2026-08-01',
            'due_date' => '2026-08-08',
            'status' => 'draft',
        ], [
            [
                'description' => 'Cash purchase',
                'quantity' => 1,
                'unit_price' => 250,
            ],
        ]);

        $voucher = app(PurchaseInvoiceService::class)->generateVoucher($invoice->fresh());
        $this->assertNotNull($voucher);

        $voucher->load('lines');

        $cashCredit = $voucher->lines->firstWhere('account_id', $cash->id);
        $this->assertNotNull($cashCredit);
        $this->assertEquals(250.0, (float) $cashCredit->credit);

        $apCredit = $voucher->lines->firstWhere('account_id', $accounts['ap']->id);
        $this->assertNull($apCredit);
    }
}
