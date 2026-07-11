<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\LedgerService;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportApiController extends Controller
{
    protected ReportService $reportService;
    protected LedgerService $ledgerService;

    public function __construct(ReportService $reportService, LedgerService $ledgerService)
    {
        $this->reportService = $reportService;
        $this->ledgerService = $ledgerService;
    }

    /**
     * Get Profit & Loss report
     */
    public function profitLoss(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        $report = $this->reportService->getProfitLoss($companyId, $financialYearId);

        return ResponseHelper::success($report);
    }

    /**
     * Get Balance Sheet report
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        $report = $this->reportService->getBalanceSheet($companyId, $financialYearId);

        return ResponseHelper::success($report);
    }

    /**
     * Get Trial Balance report
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $financialYearId = $request->input('financial_year_id') ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        $report = $this->ledgerService->getTrialBalance($companyId, $financialYearId);

        return ResponseHelper::success($report);
    }

    /**
     * Get Day Book report
     */
    public function dayBook(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $companyId = $request->user()->company_id;
        $report = $this->reportService->getDayBook($companyId, $request->date);

        return ResponseHelper::success($report);
    }

    /**
     * Cash Book
     */
    public function cashBook(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $report = $this->reportService->getCashBankBook(
            $companyId,
            'cash',
            $request->filled('account_id') ? (int) $request->account_id : null,
            $request->date_from,
            $request->date_to,
            $request->financial_year_id
        );

        return ResponseHelper::success($report);
    }

    /**
     * Bank Book
     */
    public function bankBook(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $report = $this->reportService->getCashBankBook(
            $companyId,
            'bank',
            $request->filled('account_id') ? (int) $request->account_id : null,
            $request->date_from,
            $request->date_to,
            $request->financial_year_id
        );

        return ResponseHelper::success($report);
    }

    /**
     * Get Ledger report
     */
    public function ledger(Request $request): JsonResponse
    {
        $request->validate([
            'account_id' => 'required|exists:accounts,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $companyId = $request->user()->company_id;
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        $report = $this->ledgerService->getAccountLedger(
            $request->account_id,
            $companyId,
            $financialYearId,
            $request->date_from,
            $request->date_to
        );

        return ResponseHelper::success($report);
    }

    /**
     * Get Debtors Outstanding report
     */
    public function debtorsOutstanding(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $report = $this->reportService->getDebtorsOutstanding($companyId);

        return ResponseHelper::success($report);
    }

    /**
     * Get Creditors Outstanding report
     */
    public function creditorsOutstanding(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $report = $this->reportService->getCreditorsOutstanding($companyId);

        return ResponseHelper::success($report);
    }
}
