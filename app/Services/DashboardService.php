<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Ledger;
use App\Models\Party;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Resolve dashboard date range from filter values.
     * "this_year" follows the current financial year so cards match Profit & Loss.
     */
    public function resolveDateRange(string $range, ?int $companyId = null): array
    {
        $now = Carbon::now();

        return match ($range) {
            'this_month' => [
                'start' => $now->copy()->startOfMonth()->toDateString(),
                'end' => $now->copy()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                'start' => $now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                'end' => $now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'this_quarter' => [
                'start' => $now->copy()->startOfQuarter()->toDateString(),
                'end' => $now->copy()->endOfQuarter()->toDateString(),
            ],
            default => $this->financialYearRange($companyId, $now),
        };
    }

    /**
     * Get dashboard statistics from posted ledger entries (same source as reports).
     */
    public function getStatistics(int $companyId, ?string $startDate = null, ?string $endDate = null): array
    {
        $financialYear = FinancialYear::getCurrent($companyId);
        $financialYearId = $financialYear?->id;
        $isFullFinancialYear = $this->coversFinancialYear($financialYear, $startDate, $endDate);

        $periodStart = $startDate ?? $financialYear?->start_date->toDateString();
        $periodEnd = $endDate ?? $financialYear?->end_date->toDateString();

        if ($isFullFinancialYear && $financialYearId) {
            // Whole financial year: reuse the report so cards are identical to the Profit & Loss page.
            $startDate = null;
            $endDate = null;

            $profitLoss = $this->reportService->getProfitLoss($companyId, $financialYearId);
            $income = round((float) $profitLoss['income']['total'], 2);
            $expense = round((float) $profitLoss['expense']['total'], 2);
        } else {
            $movement = $this->incomeExpenseMovement($companyId, $financialYearId, $startDate, $endDate);
            $income = $movement['income'];
            $expense = $movement['expense'];
        }

        $partyStats = $this->getPartyStatistics($companyId);

        $debtorsOutstanding = $this->reportService->getDebtorsOutstanding($companyId);
        $creditorsOutstanding = $this->reportService->getCreditorsOutstanding($companyId);

        return [
            'income' => $income,
            'expense' => $expense,
            'profit' => round($income - $expense, 2),
            'receivables' => round((float) ($debtorsOutstanding['total'] ?? 0), 2),
            'payables' => round((float) ($creditorsOutstanding['total'] ?? 0), 2),
            'cash_balance' => $this->cashBankBalance($companyId, $financialYearId, $endDate),
            'total_vouchers' => $this->countPostedVouchers($companyId, $financialYearId, $startDate, $endDate),
            'total_parties' => $partyStats['total'] ?? 0,
            'total_accounts' => $partyStats['accounts'] ?? 0,
            'period' => [
                'start' => $periodStart,
                'end' => $periodEnd,
                'label' => $this->periodLabel($financialYear, $isFullFinancialYear, $periodStart, $periodEnd),
            ],
        ];
    }

    /**
     * Human readable label for the statistics period.
     */
    protected function periodLabel(
        ?FinancialYear $financialYear,
        bool $isFullFinancialYear,
        ?string $periodStart,
        ?string $periodEnd
    ): string {
        if ($isFullFinancialYear && $financialYear) {
            return $financialYear->name;
        }

        if (!$periodStart || !$periodEnd) {
            return 'All time';
        }

        return Carbon::parse($periodStart)->format('d M Y') . ' - ' . Carbon::parse($periodEnd)->format('d M Y');
    }

    /**
     * Start/end dates of the current financial year, falling back to the calendar year.
     */
    protected function financialYearRange(?int $companyId, Carbon $now): array
    {
        $financialYear = $companyId ? FinancialYear::getCurrent($companyId) : null;

        if ($financialYear) {
            return [
                'start' => $financialYear->start_date->toDateString(),
                'end' => $financialYear->end_date->toDateString(),
            ];
        }

        return [
            'start' => $now->copy()->startOfYear()->toDateString(),
            'end' => $now->copy()->endOfYear()->toDateString(),
        ];
    }

    protected function coversFinancialYear(?FinancialYear $financialYear, ?string $startDate, ?string $endDate): bool
    {
        if (!$financialYear || !$startDate || !$endDate) {
            return false;
        }

        return $startDate === $financialYear->start_date->toDateString()
            && $endDate === $financialYear->end_date->toDateString();
    }

    /**
     * Base ledger query joined to accounts, scoped to company, financial year and dates.
     */
    protected function ledgerMovementQuery(
        int $companyId,
        ?int $financialYearId,
        ?string $startDate,
        ?string $endDate
    ): Builder {
        $query = Ledger::query()
            ->join('accounts', 'accounts.id', '=', 'ledgers.account_id')
            ->where('ledgers.company_id', $companyId)
            ->whereNull('accounts.deleted_at');

        if ($financialYearId) {
            $query->where('ledgers.financial_year_id', $financialYearId);
        }

        if ($startDate) {
            $query->where('ledgers.transaction_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('ledgers.transaction_date', '<=', $endDate);
        }

        return $query;
    }

    /**
     * Income and expense movement, excluding tax and control accounts by design
     * because those balances live on their own asset/liability ledgers.
     */
    protected function incomeExpenseMovement(
        int $companyId,
        ?int $financialYearId,
        ?string $startDate,
        ?string $endDate
    ): array {
        $rows = $this->ledgerMovementQuery($companyId, $financialYearId, $startDate, $endDate)
            ->whereIn('accounts.account_type', ['income', 'expense'])
            ->groupBy('accounts.account_type')
            ->selectRaw('accounts.account_type as account_type')
            ->selectRaw('COALESCE(SUM(ledgers.debit), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(ledgers.credit), 0) as credit_total')
            ->get();

        $income = 0.0;
        $expense = 0.0;

        foreach ($rows as $row) {
            $debit = (float) $row->debit_total;
            $credit = (float) $row->credit_total;

            if ($row->account_type === 'income') {
                $income = $credit - $debit;
                continue;
            }

            $expense = $debit - $credit;
        }

        return [
            'income' => round($income, 2),
            'expense' => round($expense, 2),
        ];
    }

    /**
     * Cash + Bank + OD balance up to the given date.
     */
    protected function cashBankBalance(int $companyId, ?int $financialYearId, ?string $endDate): float
    {
        $row = $this->ledgerMovementQuery($companyId, $financialYearId, null, $endDate)
            ->where('accounts.is_cash_bank_od', true)
            ->selectRaw('COALESCE(SUM(ledgers.debit), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(ledgers.credit), 0) as credit_total')
            ->first();

        return round(((float) ($row->debit_total ?? 0)) - ((float) ($row->credit_total ?? 0)), 2);
    }

    /**
     * Signed balance of a control account (AR / AP) up to the given date.
     */
    protected function controlAccountBalance(
        int $companyId,
        ?int $financialYearId,
        string $accountCode,
        string $normalType,
        ?string $endDate
    ): float {
        $row = $this->ledgerMovementQuery($companyId, $financialYearId, null, $endDate)
            ->where('accounts.account_code', $accountCode)
            ->selectRaw('COALESCE(SUM(ledgers.debit), 0) as debit_total')
            ->selectRaw('COALESCE(SUM(ledgers.credit), 0) as credit_total')
            ->first();

        $debit = (float) ($row->debit_total ?? 0);
        $credit = (float) ($row->credit_total ?? 0);

        return round($normalType === 'debit' ? $debit - $credit : $credit - $debit, 2);
    }

    protected function countPostedVouchers(
        int $companyId,
        ?int $financialYearId,
        ?string $startDate,
        ?string $endDate
    ): int {
        $query = Voucher::where('company_id', $companyId)
            ->where('status', 'posted');

        if ($financialYearId) {
            $query->where('financial_year_id', $financialYearId);
        }

        if ($startDate) {
            $query->where('voucher_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('voucher_date', '<=', $endDate);
        }

        return $query->count();
    }

    /**
     * Get party statistics
     */
    protected function getPartyStatistics(int $companyId): array
    {
        $totalParties = Party::where('company_id', $companyId)->count();
        $debtors = Party::where('company_id', $companyId)->where('type', 'debtor')->count();
        $creditors = Party::where('company_id', $companyId)->where('type', 'creditor')->count();
        $totalAccounts = Account::where('company_id', $companyId)->count();

        return [
            'total' => $totalParties,
            'debtors' => $debtors,
            'creditors' => $creditors,
            'accounts' => $totalAccounts,
        ];
    }

    /**
     * Get recent transactions
     */
    public function getRecentTransactions(int $companyId, int $limit = 10): array
    {
        return Voucher::where('company_id', $companyId)
            ->where('status', 'posted')
            ->with(['party'])
            ->orderBy('voucher_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Income vs expense chart data, bucketed by month, quarter or year.
     */
    public function getChartData(int $companyId, string $group, string $startDate, string $endDate): array
    {
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;
        $labels = [];
        $incomeData = [];
        $expenseData = [];

        foreach ($this->buildBuckets($group, $startDate, $endDate) as $bucket) {
            $movement = $this->incomeExpenseMovement(
                $companyId,
                $financialYearId,
                $bucket['start'],
                $bucket['end']
            );

            $labels[] = $bucket['label'];
            $incomeData[] = $movement['income'];
            $expenseData[] = $movement['expense'];
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
        ];
    }

    /**
     * Build period buckets between two dates.
     */
    protected function buildBuckets(string $group, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();
        $current = $start->copy();
        $buckets = [];

        while ($current->lessThanOrEqualTo($end)) {
            if ($group === 'quarterly') {
                $bucketStart = $current->copy()->startOfQuarter();
                $bucketEnd = $current->copy()->endOfQuarter()->startOfDay();
                $label = sprintf('Q%s %s', (int) ceil($current->month / 3), $current->year);
            } elseif ($group === 'yearly') {
                $bucketStart = $current->copy()->startOfYear();
                $bucketEnd = $current->copy()->endOfYear()->startOfDay();
                $label = $current->format('Y');
            } else {
                $bucketStart = $current->copy()->startOfMonth();
                $bucketEnd = $current->copy()->endOfMonth()->startOfDay();
                $label = $current->format('M Y');
            }

            $buckets[] = [
                'label' => $label,
                'start' => $bucketStart->lessThan($start) ? $start->toDateString() : $bucketStart->toDateString(),
                'end' => $bucketEnd->greaterThan($end) ? $end->toDateString() : $bucketEnd->toDateString(),
            ];

            $current = $bucketEnd->copy()->addDay();
        }

        return $buckets;
    }

    /**
     * Monthly income/expense data for a calendar year.
     */
    public function getMonthlyData(int $companyId, int $year): array
    {
        $months = [];
        $incomeData = [];
        $expenseData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = sprintf('%d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));

            $movement = $this->incomeExpenseMovement($companyId, null, $startDate, $endDate);

            $months[] = date('M', mktime(0, 0, 0, $month, 1));
            $incomeData[] = $movement['income'];
            $expenseData[] = $movement['expense'];
        }

        return [
            'months' => $months,
            'income' => $incomeData,
            'expense' => $expenseData,
        ];
    }

    /**
     * Outstanding receivables at each month end.
     */
    public function getReceivablesTrend(
        int $companyId,
        ?string $startDate = null,
        ?string $endDate = null,
        int $months = 6
    ): array {
        return $this->controlAccountTrend(
            $companyId,
            Account::CODE_AR,
            'debit',
            $startDate,
            $endDate,
            $months
        );
    }

    /**
     * Outstanding payables at each month end.
     */
    public function getPayablesTrend(
        int $companyId,
        ?string $startDate = null,
        ?string $endDate = null,
        int $months = 6
    ): array {
        return $this->controlAccountTrend(
            $companyId,
            Account::CODE_AP,
            'credit',
            $startDate,
            $endDate,
            $months
        );
    }

    /**
     * Closing balance of a control account at each month end in the period.
     */
    protected function controlAccountTrend(
        int $companyId,
        string $accountCode,
        string $normalType,
        ?string $startDate,
        ?string $endDate,
        int $months
    ): array {
        $financialYearId = FinancialYear::getCurrent($companyId)?->id;

        if (!$startDate || !$endDate) {
            $startDate = now()->subMonths(max($months - 1, 0))->startOfMonth()->toDateString();
            $endDate = now()->endOfMonth()->toDateString();
        }

        // A running balance has nothing to show for months that have not happened yet.
        $currentMonthEnd = now()->endOfMonth()->toDateString();
        if ($endDate > $currentMonthEnd) {
            $endDate = $currentMonthEnd;
        }

        if ($startDate > $endDate) {
            $startDate = Carbon::parse($endDate)->startOfMonth()->toDateString();
        }

        $labels = [];
        $data = [];

        foreach ($this->buildBuckets('monthly', $startDate, $endDate) as $bucket) {
            $labels[] = $bucket['label'];
            $data[] = $this->controlAccountBalance(
                $companyId,
                $financialYearId,
                $accountCode,
                $normalType,
                $bucket['end']
            );
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get platform-level statistics for super admin.
     */
    public function getPlatformStatistics(): array
    {
        $monthlyRevenue = SubscriptionPayment::completed()
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $yearlyRevenue = SubscriptionPayment::completed()
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $trialCompanies = Subscription::query()
            ->where('status', 'trial')
            ->distinct('company_id')
            ->count('company_id');

        $expiredCompanies = Subscription::query()
            ->where(function ($query) {
                $query->where('status', 'expired')
                    ->orWhere(function ($subQuery) {
                        $subQuery->whereNotNull('current_period_end')
                            ->whereDate('current_period_end', '<', now())
                            ->whereNotIn('status', ['cancelled']);
                    });
            })
            ->distinct('company_id')
            ->count('company_id');

        return [
            'total_companies' => Company::count(),
            'active_companies' => Company::where('is_active', true)->count(),
            'inactive_companies' => Company::where('is_active', false)->count(),
            'trial_companies' => $trialCompanies,
            'expired_companies' => $expiredCompanies,
            'monthly_revenue' => (float) $monthlyRevenue,
            'yearly_revenue' => (float) $yearlyRevenue,
            'active_users' => User::where('status', 'active')->count(),
            'total_transactions' => Voucher::count(),
        ];
    }

    /**
     * Recent tenant registrations for super admin dashboard.
     */
    public function getRecentCompanyRegistrations(int $limit = 6)
    {
        return Company::with('users')
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Recent subscription payments for super admin dashboard.
     */
    public function getRecentPlatformPayments(int $limit = 6)
    {
        return SubscriptionPayment::with(['company', 'subscription.plan'])
            ->latest(DB::raw('COALESCE(paid_at, created_at)'))
            ->limit($limit)
            ->get();
    }

    /**
     * Companies with subscriptions nearing expiry.
     */
    public function getSubscriptionExpiryAlerts(int $days = 10)
    {
        return Subscription::with(['company', 'plan'])
            ->whereIn('status', ['trial', 'active'])
            ->whereNotNull('current_period_end')
            ->whereBetween('current_period_end', [now()->startOfDay(), now()->copy()->addDays($days)->endOfDay()])
            ->orderBy('current_period_end')
            ->limit(8)
            ->get();
    }
}
