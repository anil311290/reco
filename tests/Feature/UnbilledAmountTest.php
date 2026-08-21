<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use App\Services\PartyService;
use App\Services\ReportService;
use App\Services\SalesInvoiceService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class UnbilledAmountTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_balance_can_be_allocated_billwise_to_a_sales_invoice(): void
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
            'is_closed' => false,
        ]);
        $debtor = app(PartyService::class)->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'name' => 'Opening Balance Customer',
            'type' => 'debtor',
            'opening_balance' => 1000,
            'opening_balance_type' => 'debit',
            'is_active' => true,
        ]);

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-OB-001',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Opening bill', 'quantity' => 1, 'unit_price' => 1000, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $salesService->generateVoucher($invoice);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'role' => 'superadmin',
            'status' => 'active',
        ]);

        $response = $this->actingAs($user)->postJson(
            "/admin/parties/{$debtor->id}/apply-unbilled",
            ['invoice_id' => $invoice->id, 'amount' => 1000, 'source' => 'opening_balance']
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('vouchers', [
            'company_id' => $company->id,
            'party_id' => $debtor->id,
            'voucher_type' => 'adjustment',
        ]);

        $invoice->refresh();
        $this->assertSame(0.0, (float) $invoice->balance_due);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame(0.0, app(ReportService::class)->getPartyOpeningBalanceAvailable(
            $company->id,
            $debtor->id,
            $fy->id
        ));
    }

    public function test_unbilled_amount_is_detected_and_can_be_applied_to_an_invoice(): void
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

        $salesService = app(SalesInvoiceService::class);
        $invoice = $salesService->create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'invoice_number' => 'INV-900001',
            'invoice_date' => '2026-07-10',
            'due_date' => '2026-07-20',
            'status' => 'draft',
        ], [
            ['description' => 'Widget', 'quantity' => 1, 'unit_price' => 1000, 'discount_percentage' => 0, 'tax_amount' => 0],
        ]);
        $salesService->generateVoucher($invoice);

        // Standalone advance receipt of 1500 — not allocated to any invoice.
        app(VoucherService::class)->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'party_id' => $debtor->id,
            'voucher_type' => 'receipt',
            'voucher_date' => '2026-07-11',
            'narration' => 'Advance from customer',
            'reference_number' => 'ADV-001',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1500, 'credit' => 0],
                ['account_id' => $debtor->account_id, 'party_id' => $debtor->id, 'debit' => 0, 'credit' => 1500],
            ],
        ]);

        $reportService = app(ReportService::class);
        $unbilled = $reportService->getPartyUnbilledAmount(
            $company->id,
            $debtor->id,
            'debtor',
            (float) $invoice->fresh()->balance_due,
            $fy->id,
            '2026-07-31'
        );

        $this->assertEquals(1500.0, $unbilled);

        $user = User::factory()->create(['company_id' => $company->id, 'role' => 'superadmin', 'status' => 'active']);

        $response = $this->actingAs($user)->postJson(
            "/admin/parties/{$debtor->id}/apply-unbilled",
            ['invoice_id' => $invoice->id, 'amount' => 1000]
        );

        $response->assertOk();
        $response->assertJson(['success' => true]);

        $invoice->refresh();
        $this->assertEquals(0.0, (float) $invoice->balance_due);
        $this->assertEquals('paid', $invoice->status);

        $remainingUnbilled = $reportService->getPartyUnbilledAmount(
            $company->id,
            $debtor->id,
            'debtor',
            0.0,
            $fy->id,
            '2026-07-31'
        );

        $this->assertEquals(500.0, $remainingUnbilled);
    }
}
