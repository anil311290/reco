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
        $financialYearId = $request->filled('financial_year_id')
            ? (int) $request->financial_year_id
            : \App\Models\FinancialYear::getCurrent($companyId)?->id;
        $report = $this->reportService->getDayBook($companyId, $request->date, $financialYearId);

        return ResponseHelper::success($report);
    }

    /**
     * Receipt & Payment
     */
    public function receiptPayment(Request $request): JsonResponse
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'financial_year_id' => 'nullable|integer',
        ]);

        $companyId = $request->user()->company_id;
        $report = $this->reportService->getReceiptPayment(
            $companyId,
            $request->date_from,
            $request->date_to,
            $request->filled('financial_year_id')
                ? (int) $request->financial_year_id
                : FinancialYear::getCurrent($companyId)?->id
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

    /**
     * Get settlement details for a specific invoice
     */
    public function invoiceSettlementDetails(Request $request): JsonResponse
    {
        $invoiceType = $request->input('invoice_type'); // 'sales' or 'purchase'
        $invoiceId = $request->input('invoice_id');

        if (!$invoiceType || !$invoiceId) {
            return ResponseHelper::error('invoice_type and invoice_id are required');
        }

        if (!in_array($invoiceType, ['sales', 'purchase'])) {
            return ResponseHelper::error('invoice_type must be sales or purchase');
        }

        $report = $this->reportService->getInvoiceSettlementDetails($invoiceType, $invoiceId);

        return ResponseHelper::success($report);
    }

    /**
     * Get settlement details for a specific payment/receipt voucher
     */
    public function paymentSettlementDetails(Request $request): JsonResponse
    {
        $voucherId = $request->input('voucher_id');

        if (!$voucherId) {
            return ResponseHelper::error('voucher_id is required');
        }

        $report = $this->reportService->getPaymentSettlementDetails($voucherId);

        return ResponseHelper::success($report);
    }

    /**
     * Get settlement audit report with filters
     */
    public function settlementAuditReport(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $dateFrom = $request->input('date_from') ? \Carbon\Carbon::parse($request->input('date_from')) : null;
        $dateTo = $request->input('date_to') ? \Carbon\Carbon::parse($request->input('date_to')) : null;
        $filters = $request->input('filters', []);

        $report = $this->reportService->getSettlementAuditReport($companyId, $dateFrom, $dateTo, $filters);

        return ResponseHelper::success($report);
    }
}
