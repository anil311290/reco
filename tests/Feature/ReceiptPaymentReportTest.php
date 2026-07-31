<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use App\Services\ReportService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReceiptPaymentReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_groups_movement_by_head_and_balances_both_sides(): void
    {
        [$company, $fy] = $this->seedBooks();
        $this->postTransactions($company->id, $fy->id);

        $report = app(ReportService::class)->getReceiptPayment($company->id, null, null, $fy->id);

        $this->assertSame('2026-04-01', $report['date_from']);
        $this->assertSame('2027-03-31', $report['date_to']);

        $this->assertEqualsWithDelta(1215.40, (float) $report['receipts']['total'], 0.01);
        $this->assertEqualsWithDelta(1475.00, (float) $report['payments']['total'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $report['opening_total'], 0.01);
        $this->assertEqualsWithDelta(-259.60, (float) $report['closing_total'], 0.01);
        $this->assertEqualsWithDelta(
            (float) $report['receipts_side_total'],
            (float) $report['payments_side_total'],
            0.01
        );
        $this->assertTrue($report['is_balanced']);

        $receiptHeads = collect($report['receipts']['rows'])->pluck('amount', 'label');
        $this->assertEqualsWithDelta(1030.00, (float) $receiptHeads['Sales Revenue'], 0.01);
        $this->assertEqualsWithDelta(185.40, (float) $receiptHeads['Output Tax Payable'], 0.01);

        $paymentHeads = collect($report['payments']['rows'])->pluck('amount', 'label');
        $this->assertEqualsWithDelta(1250.00, (float) $paymentHeads['Purchase Expenses'], 0.01);
        $this->assertEqualsWithDelta(225.00, (float) $paymentHeads['Input Tax Credit'], 0.01);
    }

    public function test_transfer_between_cash_and_bank_is_excluded_from_both_sides(): void
    {
        [$company, $fy] = $this->seedBooks();

        $cash = $this->account($company->id, 'CASH01');
        $bank = $this->account($company->id, 'BANK01');
        $revenue = $this->account($company->id, 'INC001');

        /** @var VoucherService $voucherService */
        $voucherService = app(VoucherService::class);

        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'income',
            'voucher_date' => '2026-07-15',
            'status' => 'posted',
            'narration' => 'Cash sale',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 5000],
            ],
        ]);

        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'contra',
            'voucher_date' => '2026-07-16',
            'status' => 'posted',
            'narration' => 'Cash deposited into bank',
            'lines' => [
                ['account_id' => $bank->id, 'debit' => 2000, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 2000],
            ],
        ]);

        $report = app(ReportService::class)->getReceiptPayment($company->id, null, null, $fy->id);

        $this->assertEqualsWithDelta(5000.0, (float) $report['receipts']['total'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $report['payments']['total'], 0.01);
        $this->assertEqualsWithDelta(5000.0, (float) $report['closing_total'], 0.01);
        $this->assertTrue($report['is_balanced']);

        $cashRow = collect($report['accounts'])->firstWhere('account.account_code', 'CASH01');
        $bankRow = collect($report['accounts'])->firstWhere('account.account_code', 'BANK01');
        $this->assertEqualsWithDelta(3000.0, (float) $cashRow['closing'], 0.01);
        $this->assertEqualsWithDelta(2000.0, (float) $bankRow['closing'], 0.01);
    }

    public function test_same_head_can_appear_on_both_receipt_and_payment_sides(): void
    {
        [$company, $fy] = $this->seedBooks();

        $cash = $this->account($company->id, 'CASH01');
        $sales = $this->account($company->id, 'INC001');

        /** @var VoucherService $voucherService */
        $voucherService = app(VoucherService::class);

        // Cash receipt from Sales head.
        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'income',
            'voucher_date' => '2026-07-10',
            'status' => 'posted',
            'narration' => 'Sale receipt',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 500, 'credit' => 0],
                ['account_id' => $sales->id, 'debit' => 0, 'credit' => 500],
            ],
        ]);

        // Cash payment posted back to the same head (discount/adjustment-style reversal).
        $voucherService->create([
            'company_id' => $company->id,
            'financial_year_id' => $fy->id,
            'voucher_type' => 'payment',
            'voucher_date' => '2026-07-11',
            'status' => 'posted',
            'narration' => 'Sales head adjustment paid in cash',
            'lines' => [
                ['account_id' => $sales->id, 'debit' => 200, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 200],
            ],
        ]);

        $report = app(ReportService::class)->getReceiptPayment($company->id, null, null, $fy->id);

        $receiptHeads = collect($report['receipts']['rows'])->pluck('amount', 'label');
        $paymentHeads = collect($report['payments']['rows'])->pluck('amount', 'label');

        $this->assertEqualsWithDelta(500.0, (float) $receiptHeads['Sales Revenue'], 0.01);
        $this->assertEqualsWithDelta(200.0, (float) $paymentHeads['Sales Revenue'], 0.01);
        $this->assertEqualsWithDelta(500.0, (float) $report['receipts']['total'], 0.01);
        $this->assertEqualsWithDelta(200.0, (float) $report['payments']['total'], 0.01);
        $this->assertTrue($report['is_balanced']);
    }

    public function test_opening_balance_reflects_movement_before_the_period(): void
    {
        [$company, $fy] = $this->seedBooks();
        $this->postTransactions($company->id, $fy->id);

        $report = app(ReportService::class)->getReceiptPayment(
            $company->id,
            '2026-07-18',
            '2027-03-31',
            $fy->id
        );

        // Only the 1,475.00 purchase payment falls inside the period.
        $this->assertEqualsWithDelta(1215.40, (float) $report['opening_total'], 0.01);
        $this->assertEqualsWithDelta(0.0, (float) $report['receipts']['total'], 0.01);
        $this->assertEqualsWithDelta(1475.00, (float) $report['payments']['total'], 0.01);
        $this->assertEqualsWithDelta(-259.60, (float) $report['closing_total'], 0.01);
        $this->assertTrue($report['is_balanced']);
    }

    public function test_report_reports_missing_cash_ledger(): void
    {
        $company = Company::factory()->create();
        FinancialYear::factory()->create([
            'company_id' => $company->id,
            'is_current' => true,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ]);

        $report = app(ReportService::class)->getReceiptPayment($company->id);

        $this->assertNotNull($report['message']);
        $this->assertSame([], $report['accounts']);
        $this->assertTrue($report['is_balanced']);
    }

    public function test_api_returns_the_same_figures_as_the_service(): void
    {
        [$company, $fy] = $this->seedBooks();
        $this->postTransactions($company->id, $fy->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/reports/receipt-payment')
            ->assertOk()
            ->assertJsonPath('data.opening_total', 0)
            ->assertJsonPath('data.receipts.total', 1215.4)
            ->assertJsonPath('data.payments.total', 1475)
            ->assertJsonPath('data.closing_total', -259.6)
            ->assertJsonPath('data.is_balanced', true);
    }

    public function test_web_report_page_renders(): void
    {
        $this->seed();
        $user = User::where('email', 'superadmin@reco.app')->firstOrFail();

        Account::factory()->create([
            'company_id' => $user->company_id,
            'financial_year_id' => FinancialYear::getCurrent($user->company_id)?->id,
            'account_code' => 'CASH01',
            'account_name' => 'Cash in Hand',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'balance_type' => 'debit',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get('/admin/reports/receipt-payment');

        $response->assertOk()
            ->assertSee('Receipt &amp; Payment', false)
            ->assertSee('Closing Balance c/f');
    }

    public function test_pdf_and_excel_exports_are_generated(): void
    {
        $this->seed();
        $user = User::where('email', 'superadmin@reco.app')->firstOrFail();

        Account::factory()->create([
            'company_id' => $user->company_id,
            'financial_year_id' => FinancialYear::getCurrent($user->company_id)?->id,
            'account_code' => 'CASH01',
            'account_name' => 'Cash in Hand',
            'account_type' => 'asset',
            'is_cash_bank_od' => true,
            'balance_type' => 'debit',
            'opening_balance' => 0,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/export/receipt-payment/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($user)
            ->get('/admin/export/receipt-payment/excel')
            ->assertOk();
    }

    public function test_removed_cash_and_bank_book_routes_are_gone(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->get('/admin/reports/cash-book')->assertNotFound();
        $this->actingAs($user)->get('/admin/reports/bank-book')->assertNotFound();

        Sanctum::actingAs($user);
        $this->getJson('/api/v1/reports/cash-book')->assertNotFound();
        $this->getJson('/api/v1/reports/bank-book')->assertNotFound();
    }

    private function account(int $companyId, string $code): Account
    {
        return Account::where('company_id', $companyId)
            ->where('account_code', $code)
            ->firstOrFail();
    }

    private function postTransactions(int $companyId, int $financialYearId): void
    {
        /** @var VoucherService $voucherService */
        $voucherService = app(VoucherService::class);

        $cash = $this->account($companyId, 'CASH01');
        $revenue = $this->account($companyId, 'INC001');
        $salesTax = $this->account($companyId, Account::CODE_SALES_TAX);
        $purchase = $this->account($companyId, 'EXP001');
        $purchaseTax = $this->account($companyId, Account::CODE_PURCHASE_TAX);

        $voucherService->create([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_type' => 'income',
            'voucher_date' => '2026-07-15',
            'status' => 'posted',
            'narration' => 'Sales with GST',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1215.40, 'credit' => 0],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1030.00],
                ['account_id' => $salesTax->id, 'debit' => 0, 'credit' => 185.40],
            ],
        ]);

        $voucherService->create([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_type' => 'expense',
            'voucher_date' => '2026-07-20',
            'status' => 'posted',
            'narration' => 'Purchase with GST',
            'lines' => [
                ['account_id' => $purchase->id, 'debit' => 1250.00, 'credit' => 0],
                ['account_id' => $purchaseTax->id, 'debit' => 225.00, 'credit' => 0],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 1475.00],
            ],
        ]);
    }

    /**
     * @return array{0: Company, 1: FinancialYear}
     */
    private function seedBooks(): array
    {
        $company = Company::factory()->create();
        $fy = FinancialYear::factory()->create([
            'company_id' => $company->id,
            'name' => 'FY 2026-27',
            'is_current' => true,
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
        ]);

        $accounts = [
            ['CASH01', 'Cash in Hand', 'asset', 'debit', 'cash'],
            ['BANK01', 'HDFC Bank', 'asset', 'debit', 'bank'],
            ['INC001', 'Sales Revenue', 'income', 'credit', null],
            ['EXP001', 'Purchase Expenses', 'expense', 'debit', null],
            [Account::CODE_SALES_TAX, 'Output Tax Payable', 'liability', 'credit', null],
            [Account::CODE_PURCHASE_TAX, 'Input Tax Credit', 'asset', 'debit', null],
            [Account::CODE_AR, 'Accounts Receivable', 'asset', 'debit', null],
            [Account::CODE_AP, 'Accounts Payable', 'liability', 'credit', null],
            [Account::CODE_SUSPENSE, 'Opening Balance Difference', 'asset', 'debit', null],
        ];

        foreach ($accounts as [$code, $name, $type, $balanceType, $mode]) {
            Account::factory()->create([
                'company_id' => $company->id,
                'financial_year_id' => $fy->id,
                'account_code' => $code,
                'account_name' => $name,
                'account_type' => $type,
                'is_cash_bank_od' => in_array($mode, ['cash', 'bank', 'od'], true),
                'balance_type' => $balanceType,
                'opening_balance' => 0,
                'is_active' => true,
            ]);
        }

        return [$company, $fy];
    }
}
