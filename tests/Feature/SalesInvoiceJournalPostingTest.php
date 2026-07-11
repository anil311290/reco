<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use App\Models\TaxRate;
use App\Services\SalesInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class SalesInvoiceJournalPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_posting_creates_ar_taxable_and_tax_journal_lines(): void
    {
        $company = Company::factory()->create();

        $financialYear = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
        ]);

        $arAccount = Account::factory()->create([
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'account_code' => Account::CODE_AR,
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'entry_source' => 'system',
            'opening_balance' => 0,
            'balance_type' => 'debit',
            'is_system' => true,
            'is_active' => true,
        ]);

        $taxRateId = DB::table('tax_rates')->insertGetId([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
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

        $taxRate = TaxRate::findOrFail($taxRateId);

        /** @var SalesInvoiceService $service */
        $service = app(SalesInvoiceService::class);

        if (DB::getDriverName() === 'sqlite') {
            DB::statement('PRAGMA ignore_check_constraints = ON');
        }

        $invoice = $service->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $financialYear->id,
            'invoice_type' => 'item',
            'invoice_number' => $service->generateInvoiceNumber($company->id, $financialYear->id, 'item'),
            'invoice_date' => '2026-07-06',
            'due_date' => '2026-07-13',
            'discount_percentage' => 0,
            'status' => 'draft',
        ], [
            [
                'description' => 'Taxable sale',
                'quantity' => 2,
                'unit_price' => 100,
                'discount_percentage' => 0,
                'tax_rate_id' => $taxRate->id,
            ],
        ]);

        $voucher = $service->generateVoucher($invoice);

        $this->assertNotNull($voucher);

        $voucher = $voucher->fresh('lines.account');
        $invoice = $invoice->fresh();

        $this->assertSame('sent', $invoice->status);
        $this->assertCount(3, $voucher->lines);

        $expectedTaxable = 200.0;
        $expectedTax = 36.0;
        $expectedTotal = 236.0;

        $arLine = $voucher->lines->firstWhere('account_id', $arAccount->id);
        $this->assertNotNull($arLine, 'AR line was not created');
        $this->assertEquals($expectedTotal, (float) $arLine->debit);
        $this->assertEquals(0.0, (float) $arLine->credit);

        $taxableLine = $voucher->lines->first(function ($line) use ($expectedTaxable) {
            return (float) $line->credit === $expectedTaxable && (float) $line->debit === 0.0;
        });
        $this->assertNotNull($taxableLine, 'Taxable sales line was not created');
        $this->assertSame('income', $taxableLine->account->account_type);

        $taxLine = $voucher->lines->first(function ($line) use ($expectedTax) {
            return (float) $line->credit === $expectedTax && (float) $line->debit === 0.0;
        });
        $this->assertNotNull($taxLine, 'Tax line was not created');
        $this->assertSame('liability', $taxLine->account->account_type);

        $journalRows = JournalEntry::where('company_id', $company->id)
            ->where('module', 'sales')
            ->where('source_type', 'sales_invoice')
            ->where('source_id', $invoice->id)
            ->orderBy('line_no')
            ->get();

        $this->assertCount(3, $journalRows);
        $this->assertEquals($expectedTaxable + $expectedTax, (float) $journalRows->sum('credit'));
        $this->assertEquals($expectedTotal, (float) $journalRows->sum('debit'));
    }
}
