<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\LedgerService;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
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
        $request->validate([
            'financial_year_id' => 'nullable|integer|exists:financial_years,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $companyId = $request->user()->company_id;
        $financialYearId = $this->resolveFinancialYearId($request) ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        $report = $this->reportService->getProfitLoss(
            $companyId,
            $financialYearId,
            $request->input('date_from'),
            $request->input('date_to')
        );

        return ResponseHelper::success($report);
    }

    /**
     * Get Balance Sheet report
     */
    public function balanceSheet(Request $request): JsonResponse
    {
        $request->validate([
            'financial_year_id' => 'nullable|integer|exists:financial_years,id',
            'as_of_date' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $companyId = $request->user()->company_id;
        $financialYearId = $this->resolveFinancialYearId($request) ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        $asOfDate = $request->input('as_of_date') ?? $request->input('date_to');

        $report = $this->reportService->getBalanceSheet(
            $companyId,
            $financialYearId,
            $asOfDate
        );

        return ResponseHelper::success($report);
    }

    /**
     * Get Trial Balance report
     */
    public function trialBalance(Request $request): JsonResponse
    {
        $request->validate([
            'financial_year_id' => 'nullable|integer|exists:financial_years,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $companyId = $request->user()->company_id;
        $financialYearId = $this->resolveFinancialYearId($request) ?? FinancialYear::getCurrent($companyId)?->id;

        if (!$financialYearId) {
            return ResponseHelper::error('No active financial year found');
        }

        // Match web: dated trial balance from ReportService (opening / movement / closing).
        $report = $this->reportService->getTrialBalance(
            $companyId,
            $financialYearId,
            $request->input('date_from'),
            $request->input('date_to')
        );

        return ResponseHelper::success($report);
    }

    /**
     * Get Day Book report
     */
    public function dayBook(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'nullable|date|required_without:date_from',
            'date_from' => 'nullable|date|required_without:date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'financial_year_id' => 'nullable|integer|exists:financial_years,id',
        ]);

        $companyId = $request->user()->company_id;
        $financialYearId = $this->resolveFinancialYearId($request)
            ?? FinancialYear::getCurrent($companyId)?->id;
        $dateFrom = $request->input('date_from') ?? $request->input('date');
        $dateTo = $request->input('date_to') ?? $dateFrom;

        $report = $this->reportService->getDayBookRange(
            $companyId,
            $dateFrom,
            $dateTo,
            $financialYearId
        );

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
            'financial_year_id' => 'nullable|integer|exists:financial_years,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $companyId = $request->user()->company_id;
        $financialYearId = $this->resolveFinancialYearId($request)
            ?? FinancialYear::getCurrent($companyId)?->id;

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
        $request->validate($this->outstandingRules());

        $report = $this->reportService->getDebtorsOutstanding(
            $request->user()->company_id,
            $this->resolveFinancialYearId($request),
            $request->input('as_of_date'),
            $this->outstandingFilters($request)
        );

        return ResponseHelper::success($report);
    }

    /**
     * Get Creditors Outstanding report
     */
    public function creditorsOutstanding(Request $request): JsonResponse
    {
        $request->validate($this->outstandingRules());

        $report = $this->reportService->getCreditorsOutstanding(
            $request->user()->company_id,
            $this->resolveFinancialYearId($request),
            $request->input('as_of_date'),
            $this->outstandingFilters($request)
        );

        return ResponseHelper::success($report);
    }

    /**
     * Combined receivables + payables aging report.
     */
    public function agingSummary(Request $request): JsonResponse
    {
        $request->validate($this->outstandingRules());

        $report = $this->reportService->getAgingSummary(
            $request->user()->company_id,
            $this->resolveFinancialYearId($request),
            $request->input('as_of_date'),
            $this->outstandingFilters($request)
        );

        return ResponseHelper::success($report);
    }

    /**
     * Receipts/payments that are not fully applied to invoices.
     */
    public function unappliedReceipts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $companyId = $request->user()->company_id;
        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->toDateString();

        $items = collect($this->reportService->getUnappliedReceiptsAndPayments($companyId, $fromDate, $toDate));

        $items->each(function (array &$item) use ($companyId, $toDate) {
            $item['allocation_source'] = $item['allocation_source'] ?? 'voucher';
            $partyId = $item['party']->id ?? ($item['party']['id'] ?? null);
            $invoiceQuery = $item['invoice_type'] === 'sales' ? SalesInvoice::query() : PurchaseInvoice::query();
            $item['invoices'] = $invoiceQuery
                ->where('company_id', $companyId)
                ->where('party_id', $partyId)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->where('balance_due', '>', 0)
                ->whereDate('invoice_date', '<=', $toDate)
                ->orderBy('due_date')
                ->orderBy('invoice_date')
                ->get()
                ->map(fn ($inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'invoice_date' => $inv->invoice_date?->toDateString(),
                    'due_date' => $inv->due_date?->toDateString(),
                    'balance_due' => (float) $inv->balance_due,
                    'total' => (float) $inv->total,
                    'status' => $inv->status,
                ])
                ->all();
            unset($item);
        });

        return ResponseHelper::success([
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'receipts' => $items->where('voucher_type', 'receipt')->values(),
            'payments' => $items->where('voucher_type', 'payment')->values(),
        ]);
    }

    /**
     * Stock movement register for a stockable item.
     */
    public function stockRegister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'item_id' => 'nullable|integer|exists:items,id',
        ]);

        $report = $this->reportService->getStockRegister(
            $request->user()->company_id,
            $validated['from_date'] ?? null,
            $validated['to_date'] ?? null,
            isset($validated['item_id']) ? (int) $validated['item_id'] : null
        );

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

    /**
     * Shared validation for the outstanding/aging family of reports.
     */
    private function outstandingRules(): array
    {
        return [
            'financial_year_id' => 'nullable|integer|exists:financial_years,id',
            'as_of_date' => 'nullable|date',
            'overdue_status' => 'nullable|in:all,due,not_due',
            'age_bucket' => 'nullable|in:all,current,1_30,31_60,61_90,91_plus,custom',
            'basis' => 'nullable|in:billed,due',
            'age_min' => 'nullable|integer|min:0',
            'age_max' => 'nullable|integer|min:0',
        ];
    }

    private function outstandingFilters(Request $request): array
    {
        return array_filter(
            $request->only(['overdue_status', 'age_bucket', 'basis', 'age_min', 'age_max']),
            fn ($value) => $value !== null && $value !== ''
        );
    }

    private function resolveFinancialYearId(Request $request): ?int
    {
        return $request->filled('financial_year_id')
            ? (int) $request->input('financial_year_id')
            : FinancialYear::getCurrent($request->user()->company_id)?->id;
    }
}
