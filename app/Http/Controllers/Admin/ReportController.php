<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\LedgerService;
use App\Services\PartyService;
use App\Models\Account;
use App\Models\FinancialYear;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected ReportService $reportService;
    protected LedgerService $ledgerService;
    protected PartyService $partyService;

    public function __construct(ReportService $reportService, LedgerService $ledgerService, PartyService $partyService)
    {
        $this->reportService = $reportService;
        $this->ledgerService = $ledgerService;
        $this->partyService = $partyService;
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
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];

        if (!$financialYearId) {
            return view('admin.reports.balance-sheet', [
                'report' => null,
                'financialYears' => $financialYears,
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
            ]);
        }

        $report = $this->reportService->getBalanceSheet($companyId, $financialYearId, $dateFrom, $dateTo);

        return view('admin.reports.balance-sheet', compact('report', 'financialYears', 'financialYearId', 'dateFrom', 'dateTo'));
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
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];
        $filters = $request->only(['overdue_status', 'age_bucket', 'age_min', 'age_max']);

        $report = $this->reportService->getDebtorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo, $filters);
        $report['aging_summary'] = $this->summarizeAgingBuckets($report['debtors'] ?? []);
        $debtors = $this->paginateReportItems($request, $report['debtors'] ?? [], 10);

        $partyId = $request->input('party_id');
        $partyWiseRows = $this->summarizePartyWise($report['debtors'] ?? [], $partyId);
        $partyWise = $this->paginateReportItems($request, $partyWiseRows, 10, 'party_page', 'party_per_page');
        $parties = $this->partyService->getForDropdown($companyId, 'debtor');

        return view('admin.reports.debtors-outstanding', compact('report', 'debtors', 'financialYearId', 'dateFrom', 'dateTo', 'financialYears', 'partyWise', 'parties', 'partyId'));
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
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];
        $filters = $request->only(['overdue_status', 'age_bucket', 'age_min', 'age_max']);

        $report = $this->reportService->getCreditorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo, $filters);
        $report['aging_summary'] = $this->summarizeAgingBuckets($report['creditors'] ?? []);
        $creditors = $this->paginateReportItems($request, $report['creditors'] ?? [], 10);

        $partyId = $request->input('party_id');
        $partyWiseRows = $this->summarizePartyWise($report['creditors'] ?? [], $partyId);
        $partyWise = $this->paginateReportItems($request, $partyWiseRows, 10, 'party_page', 'party_per_page');
        $parties = $this->partyService->getForDropdown($companyId, 'creditor');

        return view('admin.reports.creditors-outstanding', compact('report', 'creditors', 'financialYearId', 'dateFrom', 'dateTo', 'financialYears', 'partyWise', 'parties', 'partyId'));
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

        $debtorsReport = $this->reportService->getDebtorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo, $filters);
        $creditorsReport = $this->reportService->getCreditorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo, $filters);

        $rows = collect($debtorsReport['debtors'] ?? [])
            ->map(function (array $item) {
                $item['report_type'] = 'Receivable';
                return $item;
            })
            ->merge(
                collect($creditorsReport['creditors'] ?? [])->map(function (array $item) {
                    $item['report_type'] = 'Payable';
                    return $item;
                })
            )
            ->sort(function (array $a, array $b) {
                $daysCompare = (int) ($b['overdue_days'] ?? 0) <=> (int) ($a['overdue_days'] ?? 0);
                if ($daysCompare !== 0) {
                    return $daysCompare;
                }

                return (float) ($b['balance'] ?? 0) <=> (float) ($a['balance'] ?? 0);
            })
            ->values();

        $agingRows = $this->paginateReportItems($request, $rows, 10);
        $summary = [
            'receivables_total' => (float) ($debtorsReport['total'] ?? 0),
            'payables_total' => (float) ($creditorsReport['total'] ?? 0),
            'receivables' => $this->summarizeAgingBuckets($debtorsReport['debtors'] ?? []),
            'payables' => $this->summarizeAgingBuckets($creditorsReport['creditors'] ?? []),
        ];

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

    private function summarizeAgingBuckets(iterable $rows): array
    {
        $summary = [
            'current' => ['label' => 'Current', 'count' => 0, 'amount' => 0.0],
            '1_30' => ['label' => '1-30 Days', 'count' => 0, 'amount' => 0.0],
            '31_60' => ['label' => '31-60 Days', 'count' => 0, 'amount' => 0.0],
            '61_90' => ['label' => '61-90 Days', 'count' => 0, 'amount' => 0.0],
            '91_plus' => ['label' => '91+ Days', 'count' => 0, 'amount' => 0.0],
        ];

        foreach ($rows as $row) {
            $bucket = (string) ($row['age_bucket'] ?? 'current');
            if (!array_key_exists($bucket, $summary)) {
                continue;
            }

            $summary[$bucket]['count']++;
            $summary[$bucket]['amount'] += (float) ($row['overdue_amount'] ?? 0);
        }

        foreach ($summary as $bucket => $meta) {
            $summary[$bucket]['amount'] = round((float) $meta['amount'], 2);
        }

        return $summary;
    }

    /**
     * Aggregate invoice-wise outstanding rows into one row per party.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function summarizePartyWise(iterable $rows, $partyId = null): array
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
}
