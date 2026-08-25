<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\LedgerService;
use App\Services\PartyService;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use App\Models\Item;
use App\Services\ItemService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected LedgerService $ledgerService;
    protected PartyService $partyService;
    protected ItemService $itemService;

    public function __construct(
        ReportService $reportService,
        LedgerService $ledgerService,
        PartyService $partyService,
        ItemService $itemService
    )
    {
        $this->reportService = $reportService;
        $this->ledgerService = $ledgerService;
        $this->partyService = $partyService;
        $this->itemService = $itemService;
    }

    public function index()
    {
        return view('admin.reports.index');
    }

    public function profitLoss(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];

        if (!$financialYearId) {
            return view('admin.reports.profit-loss', [
                'report' => null,
                'financialYears' => $financialYears,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        $report = $this->reportService->getProfitLoss($companyId, $financialYearId, $dateFrom, $dateTo);

        return view('admin.reports.profit-loss', compact('report', 'financialYears', 'financialYearId', 'dateFrom', 'dateTo'));
    }

    public function balanceSheet(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveAsOfDateContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $asOfDate = $context['asOfDate'];
        $financialYears = $context['financialYears'];

        if (!$financialYearId) {
            return view('admin.reports.balance-sheet', [
                'report' => null,
                'financialYears' => $financialYears,
                'asOfDate' => $asOfDate,
            ]);
        }

        $report = $this->reportService->getBalanceSheet($companyId, $financialYearId, $asOfDate);

        return view('admin.reports.balance-sheet', compact('report', 'financialYears', 'financialYearId', 'asOfDate'));
    }

    public function trialBalance(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];

        if (!$financialYearId) {
            return view('admin.reports.trial-balance', [
                'report' => null,
                'financialYears' => $financialYears,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        $report = $this->reportService->getTrialBalance($companyId, $financialYearId, $dateFrom, $dateTo);
        $accounts = collect($report['accounts'] ?? []);

        return view('admin.reports.trial-balance', compact('report', 'accounts', 'financialYears', 'financialYearId', 'dateFrom', 'dateTo'));
    }

    public function dayBook(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];

        $report = $this->reportService->getDayBookRange($companyId, $dateFrom, $dateTo, $financialYearId);
        $rows = $this->paginateReportItems($request, $report['rows'] ?? [], 10);

        return view('admin.reports.day-book', compact('report', 'rows', 'dateFrom', 'dateTo', 'financialYearId', 'financialYears'));
    }

    public function receiptPayment(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];

        $report = $this->reportService->getReceiptPayment(
            $companyId,
            $dateFrom,
            $dateTo,
            $financialYearId
        );

        return view('admin.reports.receipt-payment', [
            'report' => $report,
            'financialYears' => $financialYears,
            'financialYearId' => $financialYearId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    public function ledger(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveReportContext($request, $companyId);
        $accountId = $request->input('account_id');
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYearId = $context['financialYearId'];
        $financialYears = $context['financialYears'];

        $accounts = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('account_code', '!=', Account::CODE_SUSPENSE)
            ->orderBy('account_code')
            ->get();

        $report = null;

        if ($accountId && $accountId !== 'all') {
            $selectedAccount = $accounts->firstWhere('id', (int) $accountId);

            if ($selectedAccount) {
                $report = $this->ledgerService->getAccountLedger(
                    (int) $accountId,
                    $companyId,
                    $financialYearId,
                    $dateFrom,
                    $dateTo
                );
            }
        }

        $entries = $report
            ? $this->paginateReportItems($request, $report['entries'] ?? [], 10)
            : null;

        return view('admin.reports.ledger', compact('report', 'entries', 'accounts', 'accountId', 'dateFrom', 'dateTo', 'financialYearId', 'financialYears'));
    }

    public function debtorsOutstanding(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveAsOfDateContext($request, $companyId);
        $financialYearId = null;
        $asOfDate = $context['asOfDate'];
        $financialYears = $context['financialYears'];
        $filters = $request->only(['overdue_status', 'age_bucket', 'age_min', 'age_max', 'basis']);

        $report = $this->reportService->getDebtorsOutstanding($companyId, $financialYearId, $asOfDate, $filters);

        $partyId = $request->input('party_id');
        if ($partyId) {
            $report['debtors'] = array_values(array_filter($report['debtors'] ?? [], function (array $item) use ($partyId) {
                return $item['party'] && (string) $item['party']->id === (string) $partyId;
            }));
        }

        $report['aging_summary'] = $this->reportService->summarizeAgingBuckets($report['debtors'] ?? []);
        $debtors = $this->paginateReportItems($request, $report['debtors'] ?? [], 10);

        $partyWiseRows = $this->summarizePartyWise($report['debtors'] ?? [], $partyId, 'debtor', $companyId, $financialYearId, $asOfDate);
        $partyWise = $this->paginateReportItems($request, $partyWiseRows, 10, 'party_page', 'party_per_page');
        $parties = $this->partyService->getForDropdown($companyId, 'debtor');

        return view('admin.reports.debtors-outstanding', compact('report', 'debtors', 'financialYearId', 'asOfDate', 'financialYears', 'partyWise', 'parties', 'partyId'));
    }

    /**
     * Settlement Audit Report - payment-to-invoice mapping trail.
     */
    public function settlementAudit(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];

        $filters = [
            'status' => $request->input('status', 'all'),
            'type' => $request->input('type', 'all'),
        ];

        $report = $this->reportService->getSettlementAuditReport(
            $companyId,
            $dateFrom ? Carbon::parse($dateFrom) : null,
            $dateTo ? Carbon::parse($dateTo) : null,
            $filters
        );
        $mappings = $this->paginateReportItems($request, $report['mappings'] ?? [], 25);

        return view('admin.reports.settlement-audit', compact('report', 'mappings', 'financialYearId', 'dateFrom', 'dateTo', 'financialYears', 'filters'));
    }

    public function creditorsOutstanding(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveAsOfDateContext($request, $companyId);
        $financialYearId = null;
        $asOfDate = $context['asOfDate'];
        $financialYears = $context['financialYears'];
        $filters = $request->only(['overdue_status', 'age_bucket', 'age_min', 'age_max', 'basis']);

        $report = $this->reportService->getCreditorsOutstanding($companyId, $financialYearId, $asOfDate, $filters);

        $partyId = $request->input('party_id');
        if ($partyId) {
            $report['creditors'] = array_values(array_filter($report['creditors'] ?? [], function (array $item) use ($partyId) {
                return $item['party'] && (string) $item['party']->id === (string) $partyId;
            }));
        }

        $report['aging_summary'] = $this->reportService->summarizeAgingBuckets($report['creditors'] ?? []);
        $creditors = $this->paginateReportItems($request, $report['creditors'] ?? [], 10);

        $partyWiseRows = $this->summarizePartyWise($report['creditors'] ?? [], $partyId, 'creditor', $companyId, $financialYearId, $asOfDate);
        $partyWise = $this->paginateReportItems($request, $partyWiseRows, 10, 'party_page', 'party_per_page');
        $parties = $this->partyService->getForDropdown($companyId, 'creditor');

        return view('admin.reports.creditors-outstanding', compact('report', 'creditors', 'financialYearId', 'asOfDate', 'financialYears', 'partyWise', 'parties', 'partyId'));
    }

    public function unappliedReceipts(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $validated = $request->validate([
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);
        $fromDate = $validated['from_date'] ?? now()->startOfMonth()->toDateString();
        $toDate = $validated['to_date'] ?? now()->toDateString();
        $items = $this->reportService->getUnappliedReceiptsAndPayments($companyId, $fromDate, $toDate);

        foreach ($items as &$item) {
            $invoiceQuery = $item['invoice_type'] === 'sales' ? SalesInvoice::query() : PurchaseInvoice::query();
            $item['invoices'] = $invoiceQuery
                ->where('company_id', $companyId)
                ->where('party_id', $item['party']->id)
                ->whereNotIn('status', ['paid', 'cancelled'])
                ->where('balance_due', '>', 0)
                ->whereDate('invoice_date', '<=', $toDate)
                ->orderBy('due_date')
                ->orderBy('invoice_date')
                ->get();
            $item['allocation_source'] = $item['allocation_source'] ?? 'voucher';
        }
        unset($item);

        return view('admin.reports.unapplied-receipts', [
            'receipts' => array_values(array_filter($items, fn (array $item) => $item['voucher_type'] === 'receipt')),
            'payments' => array_values(array_filter($items, fn (array $item) => $item['voucher_type'] === 'payment')),
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function stockRegister(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $selectedItemId = $request->input('item_id');

        $register = $this->reportService->getStockRegister(
            $companyId,
            $fromDate,
            $toDate,
            $selectedItemId ? (int) $selectedItemId : null
        );

        $stockRows = $this->paginateReportItems($request, $register['rows'], 25, 'stock_page', 'stock_per_page');

        return view('admin.reports.stock-register', [
            'rows' => $stockRows,
            'totalMovements' => $register['total_movements'],
            'totalIn' => $register['total_in'],
            'totalOut' => $register['total_out'],
            'closingQuantity' => $register['closing_quantity'],
            'items' => Item::query()
                ->where('company_id', $companyId)
                ->where('type', 'goods')
                ->where('is_stockable', true)
                ->orderBy('name')
                ->get(),
            'selectedItemId' => $selectedItemId,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    public function agingSummary(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];
        $filters = $request->only(['overdue_status', 'age_bucket', 'age_min', 'age_max']);

        $aging = $this->reportService->getAgingSummary($companyId, $financialYearId, $dateTo, $filters);

        $agingRows = $this->paginateReportItems($request, $aging['rows'], 10);
        $summary = $aging['summary'];

        return view('admin.reports.aging-summary', compact(
            'financialYearId',
            'financialYears',
            'dateFrom',
            'dateTo',
            'agingRows',
            'summary',
            'filters'
        ));
    }

    /**
     * Aggregate invoice-wise outstanding rows into one row per party.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function summarizePartyWise(iterable $rows, $partyId, string $partyType, int $companyId, ?int $financialYearId, ?string $asOfDate): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $party = $row['party'] ?? null;
            if (!$party) {
                continue;
            }

            if ($partyId && (string) $party->id !== (string) $partyId) {
                continue;
            }

            if (!isset($groups[$party->id])) {
                $groups[$party->id] = [
                    'party' => $party,
                    'invoice_count' => 0,
                    'invoice_total' => 0.0,
                    'amount_paid' => 0.0,
                    'balance' => 0.0,
                    'max_due_days' => null,
                ];
            }

            $groups[$party->id]['invoice_count']++;
            $groups[$party->id]['invoice_total'] += (float) ($row['invoice_total'] ?? 0);
            $groups[$party->id]['amount_paid'] += (float) ($row['amount_paid'] ?? 0);
            $groups[$party->id]['balance'] += (float) ($row['balance'] ?? 0);

            $dueDays = $row['due_days'] ?? null;
            if ($dueDays !== null && ($groups[$party->id]['max_due_days'] === null || $dueDays > $groups[$party->id]['max_due_days'])) {
                $groups[$party->id]['max_due_days'] = $dueDays;
            }
        }

        $result = array_values($groups);

        foreach ($result as &$row) {
            $row['invoice_total'] = round($row['invoice_total'], 2);
            $row['amount_paid'] = round($row['amount_paid'], 2);
            $row['balance'] = round($row['balance'], 2);
            $row['opening_balance_available'] = $this->reportService->getPartyOpeningBalanceAvailable(
                $companyId,
                (int) $row['party']->id,
                $financialYearId,
                $asOfDate
            );
            $row['unapplied_amount'] = $this->reportService->getPartyUnappliedAmount(
                $companyId,
                (int) $row['party']->id,
                $partyType,
                $row['balance'],
                $financialYearId,
                $asOfDate
            );
        }
        unset($row);

        usort($result, fn ($a, $b) => $b['balance'] <=> $a['balance']);

        return $result;
    }

    protected function paginateReportItems(Request $request, iterable $items, int $defaultPerPage = 25, string $pageName = 'page', ?string $perPageName = null): LengthAwarePaginator
    {
        $perPageName = $perPageName ?? 'per_page';
        $collection = $items instanceof Collection
            ? $items->values()
            : collect($items)->values();

        $rawPerPage = $request->input($perPageName, $defaultPerPage);
        $showAll = strtolower((string) $rawPerPage) === 'all';
        $totalCount = $collection->count();

        $perPage = $showAll ? max(1, $totalCount) : max(10, min((int) $rawPerPage, 200));
        $currentPage = $showAll ? 1 : LengthAwarePaginator::resolveCurrentPage($pageName);

        $currentItems = $collection
            ->forPage($currentPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $currentItems,
            $totalCount,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
                'pageName' => $pageName,
            ]
        );
    }

    protected function resolveReportContext(Request $request, int $companyId, bool $preferToday = false): array
    {
        $financialYears = FinancialYear::where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get();

        $financialYearId = $request->filled('financial_year_id')
            ? (int) $request->input('financial_year_id')
            : FinancialYear::getCurrent($companyId)?->id;

        $selectedFinancialYear = $financialYears->firstWhere('id', $financialYearId);

        $defaultFrom = $preferToday
            ? now()->toDateString()
            : ($selectedFinancialYear?->start_date?->format('Y-m-d') ?? now()->toDateString());
        $defaultTo = $preferToday
            ? now()->toDateString()
            : ($selectedFinancialYear?->end_date?->format('Y-m-d') ?? now()->toDateString());

        $dateFrom = $request->input('date_from', $defaultFrom);
        $dateTo = $request->input('date_to', $defaultTo);

        if ($dateFrom && $dateTo && Carbon::parse($dateFrom)->greaterThan(Carbon::parse($dateTo))) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'financialYears' => $financialYears,
            'financialYearId' => $financialYearId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * Resolve financial-year + single "as of date" context for point-in-time reports
     * (Balance Sheet, Debtors/Creditors Outstanding).
     */
    protected function resolveAsOfDateContext(Request $request, int $companyId): array
    {
        $financialYears = FinancialYear::where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get();

        $financialYearId = $request->filled('financial_year_id')
            ? (int) $request->input('financial_year_id')
            : FinancialYear::getCurrent($companyId)?->id;

        $asOfDate = $request->input('as_of_date', now()->toDateString());

        return [
            'financialYears' => $financialYears,
            'financialYearId' => $financialYearId,
            'asOfDate' => $asOfDate,
        ];
    }
}
