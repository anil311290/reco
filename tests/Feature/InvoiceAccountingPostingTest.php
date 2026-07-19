<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Party;
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

        return compact('ar', 'sales', 'ap', 'purchase');
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
            'invoice_type' => 'item',
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

    public function test_service_sales_invoice_posts_balanced_journal(): void
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
            'invoice_type' => 'service',
            'invoice_number' => 'SRV-000001',
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
}
