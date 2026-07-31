<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\ReportService;
use App\Services\VoucherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardFiguresTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_range_follows_current_financial_year(): void
    {
        [$company, $fy] = $this->seedBooks();

        $range = app(DashboardService::class)->resolveDateRange('this_year', $company->id);

        $this->assertSame('2026-04-01', $range['start']);
        $this->assertSame('2027-03-31', $range['end']);
    }

    public function test_dashboard_income_expense_and_profit_match_profit_and_loss(): void
    {
        [$company, $fy] = $this->seedBooks();
        $this->postTaxedTransactions($company->id, $fy->id);

        /** @var DashboardService $dashboardService */
        $dashboardService = app(DashboardService::class);
        /** @var ReportService $reportService */
        $reportService = app(ReportService::class);

        $range = $dashboardService->resolveDateRange('this_year', $company->id);
        $statistics = $dashboardService->getStatistics($company->id, $range['start'], $range['end']);
        $profitLoss = $reportService->getProfitLoss($company->id, $fy->id);

        // Tax is excluded: 1,030 revenue + 185.40 GST invoiced, 1,250 expense + 225 GST paid.
        $this->assertEqualsWithDelta(1030.00, (float) $statistics['income'], 0.01);
        $this->assertEqualsWithDelta(1250.00, (float) $statistics['expense'], 0.01);
        $this->assertEqualsWithDelta(-220.00, (float) $statistics['profit'], 0.01);

        $this->assertEqualsWithDelta((float) $profitLoss['income']['total'], (float) $statistics['income'], 0.01);
        $this->assertEqualsWithDelta((float) $profitLoss['expense']['total'], (float) $statistics['expense'], 0.01);
        $this->assertEqualsWithDelta((float) $profitLoss['net_profit'], (float) $statistics['profit'], 0.01);

        $this->assertSame($fy->name, $statistics['period']['label']);
        $this->assertSame('2026-04-01', $statistics['period']['start']);
        $this->assertSame('2027-03-31', $statistics['period']['end']);
    }

    public function test_cash_balance_uses_cash_and_bank_ledgers(): void
    {
        [$company, $fy] = $this->seedBooks();
        $this->postTaxedTransactions($company->id, $fy->id);

        /** @var DashboardService $dashboardService */
        $dashboardService = app(DashboardService::class);
        $range = $dashboardService->resolveDateRange('this_year', $company->id);
        $statistics = $dashboardService->getStatistics($company->id, $range['start'], $range['end']);

        // 1,215.40 received - 1,475.00 paid.
        $this->assertEqualsWithDelta(-259.60, (float) $statistics['cash_balance'], 0.01);
        $this->assertSame(2, $statistics['total_vouchers']);
    }

    public function test_chart_totals_match_statistics(): void
    {
        [$company, $fy] = $this->seedBooks();
        $this->postTaxedTransactions($company->id, $fy->id);

        /** @var DashboardService $dashboardService */
        $dashboardService = app(DashboardService::class);
        $range = $dashboardService->resolveDateRange('this_year', $company->id);
        $statistics = $dashboardService->getStatistics($company->id, $range['start'], $range['end']);
        $chart = $dashboardService->getChartData($company->id, 'monthly', $range['start'], $range['end']);

        $this->assertCount(12, $chart['labels']);
        $this->assertEqualsWithDelta((float) $statistics['income'], array_sum($chart['income']), 0.01);
        $this->assertEqualsWithDelta((float) $statistics['expense'], array_sum($chart['expense']), 0.01);
    }

    public function test_api_dashboard_returns_same_figures_as_service(): void
    {
        [$company, $fy] = $this->seedBooks();
        $this->postTaxedTransactions($company->id, $fy->id);

        $user = User::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/dashboard?range=this_year&group=monthly')->assertOk();

        $response->assertJsonPath('data.statistics.income', 1030)
            ->assertJsonPath('data.statistics.expense', 1250)
            ->assertJsonPath('data.statistics.profit', -220)
            ->assertJsonPath('data.statistics.cash_balance', -259.6)
            ->assertJsonPath('data.statistics.period.label', $fy->name)
            ->assertJsonPath('data.period.start', '2026-04-01')
            ->assertJsonPath('data.period.end', '2027-03-31');
    }

    /**
     * Sales and purchase with GST posted the same way invoices post them.
     */
    private function postTaxedTransactions(int $companyId, int $financialYearId): void
    {
        /** @var VoucherService $voucherService */
        $voucherService = app(VoucherService::class);

        $cash = Account::where('company_id', $companyId)->where('account_code', 'CASH01')->firstOrFail();
        $revenue = Account::where('company_id', $companyId)->where('account_code', 'INC001')->firstOrFail();
        $salesTax = Account::where('company_id', $companyId)->where('account_code', Account::CODE_SALES_TAX)->firstOrFail();
        $purchase = Account::where('company_id', $companyId)->where('account_code', 'EXP001')->firstOrFail();
        $purchaseTax = Account::where('company_id', $companyId)->where('account_code', Account::CODE_PURCHASE_TAX)->firstOrFail();

        $voucherService->create([
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'voucher_type' => 'income',
            'voucher_date' => '2026-07-15',
            'status' => 'posted',
            'narration' => 'Sales with GST',
            'lines' => [
                ['account_id' => $cash->id, 'debit' => 1215.40, 'credit' => 0, 'description' => 'Cash'],
                ['account_id' => $revenue->id, 'debit' => 0, 'credit' => 1030.00, 'description' => 'Sales Revenue'],
                ['account_id' => $salesTax->id, 'debit' => 0, 'credit' => 185.40, 'description' => 'Output GST'],
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
                ['account_id' => $purchase->id, 'debit' => 1250.00, 'credit' => 0, 'description' => 'Purchase Expenses'],
                ['account_id' => $purchaseTax->id, 'debit' => 225.00, 'credit' => 0, 'description' => 'Input GST'],
                ['account_id' => $cash->id, 'debit' => 0, 'credit' => 1475.00, 'description' => 'Cash'],
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
                'balance_type' => $balanceType,
                'transaction_mode' => $mode,
                'opening_balance' => 0,
                'is_active' => true,
            ]);
        }

        return [$company, $fy];
    }
}
