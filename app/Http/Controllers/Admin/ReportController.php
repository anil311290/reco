<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\LedgerService;
use App\Models\Account;
use App\Models\FinancialYear;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected LedgerService $ledgerService;

    public function __construct(ReportService $reportService, LedgerService $ledgerService)
    {
        $this->reportService = $reportService;
        $this->ledgerService = $ledgerService;
    }

    /**
     * Display reports index
     */
    public function index()
    {
        return view('admin.reports.index');
    }

    /**
     * Get Profit & Loss report
     */
    public function profitLoss(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return view('admin.reports.profit-loss', [
                'report' => null,
                'financialYears' => FinancialYear::where('company_id', $companyId)->get(),
            ]);
        }

        $report = $this->reportService->getProfitLoss($companyId, $financialYearId);
        $financialYears = FinancialYear::where('company_id', $companyId)->get();

        return view('admin.reports.profit-loss', compact('report', 'financialYears', 'financialYearId'));
    }

    /**
     * Get Balance Sheet report
     */
    public function balanceSheet(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return view('admin.reports.balance-sheet', [
                'report' => null,
                'financialYears' => FinancialYear::where('company_id', $companyId)->get(),
            ]);
        }

        $report = $this->reportService->getBalanceSheet($companyId, $financialYearId);
        $financialYears = FinancialYear::where('company_id', $companyId)->get();

        return view('admin.reports.balance-sheet', compact('report', 'financialYears', 'financialYearId'));
    }

    /**
     * Get Trial Balance report
     */
    public function trialBalance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return view('admin.reports.trial-balance', [
                'report' => null,
                'financialYears' => FinancialYear::where('company_id', $companyId)->get(),
            ]);
        }

        $report = $this->ledgerService->getTrialBalance($companyId, $financialYearId);
        $financialYears = FinancialYear::where('company_id', $companyId)->get();

        return view('admin.reports.trial-balance', compact('report', 'financialYears', 'financialYearId'));
    }

    /**
     * Get Day Book report
     */
    public function dayBook(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $date = $request->input('date', date('Y-m-d'));

        $report = $this->reportService->getDayBook($companyId, $date);

        return view('admin.reports.day-book', compact('report', 'date'));
    }

    /**
     * Get Ledger report
     */
    public function ledger(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $accountId = $request->input('account_id');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('account_code')
            ->get();

        $report = null;

        if ($accountId) {
            $financialYearId = FinancialYear::getCurrent($companyId)?->id;
            $report = $this->ledgerService->getAccountLedger(
                $accountId,
                $companyId,
                $financialYearId,
                $dateFrom,
                $dateTo
            );
        }

        return view('admin.reports.ledger', compact('report', 'accounts', 'accountId', 'dateFrom', 'dateTo'));
    }

    /**
     * Get Cash Flow report
     */
    public function cashFlow(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return view('admin.reports.cash-flow', [
                'report' => null,
                'financialYears' => FinancialYear::where('company_id', $companyId)->get(),
            ]);
        }

        $report = $this->reportService->getCashFlow($companyId, $financialYearId);
        $financialYears = FinancialYear::where('company_id', $companyId)->get();

        return view('admin.reports.cash-flow', compact('report', 'financialYears', 'financialYearId'));
    }

    /**
     * Get Debtors Outstanding report
     */
    public function debtorsOutstanding()
    {
        $companyId = auth()->user()->company_id;
        $report = $this->reportService->getDebtorsOutstanding($companyId);

        return view('admin.reports.debtors-outstanding', compact('report'));
    }

    /**
     * Get Creditors Outstanding report
     */
    public function creditorsOutstanding()
    {
        $companyId = auth()->user()->company_id;
        $report = $this->reportService->getCreditorsOutstanding($companyId);

        return view('admin.reports.creditors-outstanding', compact('report'));
    }
}
