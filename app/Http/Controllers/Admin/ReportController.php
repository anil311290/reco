<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\LedgerService;
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

    public function __construct(ReportService $reportService, LedgerService $ledgerService)
    {
        $this->reportService = $reportService;
        $this->ledgerService = $ledgerService;
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
        $accounts = $this->paginateReportItems($request, $report['accounts'] ?? [], 10);

        return view('admin.reports.trial-balance', compact('report', 'accounts', 'financialYears', 'financialYearId', 'dateFrom', 'dateTo'));
    }

    public function dayBook(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveReportContext($request, $companyId, true);
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
            ->orderBy('account_code')
            ->get();

        $report = null;

        if ($accountId && $accountId !== 'all') {
            $report = $this->ledgerService->getAccountLedger(
                (int) $accountId,
                $companyId,
                $financialYearId,
                $dateFrom,
                $dateTo
            );
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

        $report = $this->reportService->getDebtorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo);
        $debtors = $this->paginateReportItems($request, $report['debtors'] ?? [], 10);

        return view('admin.reports.debtors-outstanding', compact('report', 'debtors', 'financialYearId', 'dateFrom', 'dateTo', 'financialYears'));
    }

    public function creditorsOutstanding(Request $request)
    {
        $companyId = auth()->user()->company_id;
        $context = $this->resolveReportContext($request, $companyId);
        $financialYearId = $context['financialYearId'];
        $dateFrom = $context['dateFrom'];
        $dateTo = $context['dateTo'];
        $financialYears = $context['financialYears'];

        $report = $this->reportService->getCreditorsOutstanding($companyId, $financialYearId, $dateFrom, $dateTo);
        $creditors = $this->paginateReportItems($request, $report['creditors'] ?? [], 10);

        return view('admin.reports.creditors-outstanding', compact('report', 'creditors', 'financialYearId', 'dateFrom', 'dateTo', 'financialYears'));
    }

    protected function paginateReportItems(Request $request, iterable $items, int $defaultPerPage = 25): LengthAwarePaginator
    {
        $collection = $items instanceof Collection
            ? $items->values()
            : collect($items)->values();

        $requestedPerPage = (int) $request->input('per_page', $defaultPerPage);
        $perPage = max(10, min($requestedPerPage, 200));
        $currentPage = LengthAwarePaginator::resolveCurrentPage();

        $currentItems = $collection
            ->forPage($currentPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
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
