<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Party;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\Voucher;
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
     */
    public function resolveDateRange(string $range): array
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
            'this_year' => [
                'start' => $now->copy()->startOfYear()->toDateString(),
                'end' => $now->copy()->endOfYear()->toDateString(),
            ],
            default => [
                'start' => $now->copy()->startOfYear()->toDateString(),
                'end' => $now->copy()->endOfYear()->toDateString(),
            ],
        };
    }

    /**
     * Get dashboard statistics
     */
    public function getStatistics(int $companyId, ?string $startDate = null, ?string $endDate = null): array
    {
        $financialYear = FinancialYear::getCurrent($companyId);
        $financialYearId = $financialYear?->id;

        // Get voucher statistics for selected range or current financial year.
        $voucherStats = $this->getVoucherStatistics($companyId, $financialYearId, $startDate, $endDate);

        // Get party statistics
        $partyStats = $this->getPartyStatistics($companyId);

        // Get profit/loss from voucher totals so dashboard cards align with chart data.
        $debtorsOutstanding = $this->reportService->getDebtorsOutstanding($companyId);
        $creditorsOutstanding = $this->reportService->getCreditorsOutstanding($companyId);

        $income = $voucherStats['income'] ?? 0;
        $expense = $voucherStats['expense'] ?? 0;
        $profit = $income - $expense;

        return [
            'income' => $income,
            'expense' => $expense,
            'profit' => $profit,
            'receivables' => $debtorsOutstanding['total'] ?? 0,
            'payables' => $creditorsOutstanding['total'] ?? 0,
            'cash_balance' => ($voucherStats['receipt'] ?? 0) - ($voucherStats['payment'] ?? 0),
            'total_vouchers' => $voucherStats['total'] ?? 0,
            'total_parties' => $partyStats['total'] ?? 0,
            'total_accounts' => $partyStats['accounts'] ?? 0,
        ];
    }

    /**
     * Get voucher statistics
     */
    protected function getVoucherStatistics(int $companyId, ?int $financialYearId, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = Voucher::where('company_id', $companyId)
            ->where('status', 'posted');

        if ($startDate && $endDate) {
            $query->whereBetween('voucher_date', [$startDate, $endDate]);
        } elseif ($financialYearId) {
            $query->where('financial_year_id', $financialYearId);
        }

        $income = (clone $query)->where('voucher_type', 'income')->sum('total_debit');
        $expense = (clone $query)->where('voucher_type', 'expense')->sum('total_debit');
        $receipt = (clone $query)->where('voucher_type', 'receipt')->sum('total_debit');
        $payment = (clone $query)->where('voucher_type', 'payment')->sum('total_debit');
        $total = (clone $query)->count();

        return [
            'income' => $income,
            'expense' => $expense,
            'receipt' => $receipt,
            'payment' => $payment,
            'total' => $total,
        ];
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
     * Get chart data for the dashboard.
     */
    public function getChartData(int $companyId, string $group, string $startDate, string $endDate): array
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();
        $labels = [];
        $incomeData = [];
        $expenseData = [];

        $current = $start->copy();

        while ($current->lessThanOrEqualTo($end)) {
            if ($group === 'quarterly') {
                $quarter = (int) ceil($current->month / 3);
                $bucketStart = $current->copy()->startOfQuarter();
                $bucketEnd = $current->copy()->endOfQuarter();
                $label = sprintf('Q%s %s', $quarter, $current->year);
                $current->addQuarter();
            } elseif ($group === 'yearly') {
                $bucketStart = $current->copy()->startOfYear();
                $bucketEnd = $current->copy()->endOfYear();
                $label = $current->format('Y');
                $current->addYear();
            } else {
                $bucketStart = $current->copy()->startOfMonth();
                $bucketEnd = $current->copy()->endOfMonth();
                $label = $current->format('M Y');
                $current->addMonth();
            }

            if ($bucketStart->greaterThan($end)) {
                break;
            }

            $bucketEnd = $bucketEnd->greaterThan($end) ? $end : $bucketEnd;

            $income = Voucher::where('company_id', $companyId)
                ->where('voucher_type', 'income')
                ->where('status', 'posted')
                ->whereBetween('voucher_date', [$bucketStart->toDateString(), $bucketEnd->toDateString()])
                ->sum('total_debit');

            $expense = Voucher::where('company_id', $companyId)
                ->where('voucher_type', 'expense')
                ->where('status', 'posted')
                ->whereBetween('voucher_date', [$bucketStart->toDateString(), $bucketEnd->toDateString()])
                ->sum('total_debit');

            $labels[] = $label;
            $incomeData[] = $income;
            $expenseData[] = $expense;
        }

        return [
            'labels' => $labels,
            'income' => $incomeData,
            'expense' => $expenseData,
        ];
    }

    protected function getTrendData(int $companyId, string $voucherType, string $startDate, string $endDate): array
    {
        $current = Carbon::parse($startDate)->copy()->startOfMonth();
        $end = Carbon::parse($endDate)->copy()->endOfMonth();
        $labels = [];
        $data = [];

        while ($current->lessThanOrEqualTo($end)) {
            $bucketStart = $current->copy()->startOfMonth();
            $bucketEnd = $current->copy()->endOfMonth();

            $value = Voucher::where('company_id', $companyId)
                ->where('voucher_type', $voucherType)
                ->where('status', 'posted')
                ->whereBetween('voucher_date', [$bucketStart->toDateString(), $bucketEnd->toDateString()])
                ->sum('total_debit');

            $labels[] = $current->format('M Y');
            $data[] = $value;
            $current->addMonth();
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get monthly income/expense data for chart
     */
    public function getMonthlyData(int $companyId, int $year): array
    {
        $months = [];
        $incomeData = [];
        $expenseData = [];

        for ($month = 1; $month <= 12; $month++) {
            $startDate = sprintf('%d-%02d-01', $year, $month);
            $endDate = date('Y-m-t', strtotime($startDate));

            $income = Voucher::where('company_id', $companyId)
                ->where('voucher_type', 'income')
                ->where('status', 'posted')
                ->whereBetween('voucher_date', [$startDate, $endDate])
                ->sum('total_debit');

            $expense = Voucher::where('company_id', $companyId)
                ->where('voucher_type', 'expense')
                ->where('status', 'posted')
                ->whereBetween('voucher_date', [$startDate, $endDate])
                ->sum('total_debit');

            $months[] = date('M', mktime(0, 0, 0, $month, 1));
            $incomeData[] = $income;
            $expenseData[] = $expense;
        }

        return [
            'months' => $months,
            'income' => $incomeData,
            'expense' => $expenseData,
        ];
    }

    /**
     * Get receivables trend data
     */
    public function getReceivablesTrend(int $companyId, ?string $startDate = null, ?string $endDate = null): array
    {
        if ($startDate && $endDate) {
            return $this->getTrendData($companyId, 'income', $startDate, $endDate);
        }

        $data = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startPeriod = $date->copy()->startOfMonth()->format('Y-m-d');
            $endPeriod = $date->copy()->endOfMonth()->format('Y-m-d');

            $receivables = Voucher::where('company_id', $companyId)
                ->where('voucher_type', 'income')
                ->where('status', 'posted')
                ->whereBetween('voucher_date', [$startPeriod, $endPeriod])
                ->sum('total_debit');

            $labels[] = $date->format('M Y');
            $data[] = $receivables;
        }

        return [
            'labels' => $labels,
            'data' => $data,
        ];
    }

    /**
     * Get payables trend data
     */
    public function getPayablesTrend(int $companyId, ?string $startDate = null, ?string $endDate = null): array
    {
        if ($startDate && $endDate) {
            return $this->getTrendData($companyId, 'expense', $startDate, $endDate);
        }

        $data = [];
        $labels = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startPeriod = $date->copy()->startOfMonth()->format('Y-m-d');
            $endPeriod = $date->copy()->endOfMonth()->format('Y-m-d');

            $payables = Voucher::where('company_id', $companyId)
                ->where('voucher_type', 'expense')
                ->where('status', 'posted')
                ->whereBetween('voucher_date', [$startPeriod, $endPeriod])
                ->sum('total_debit');

            $labels[] = $date->format('M Y');
            $data[] = $payables;
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
