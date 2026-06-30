<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    protected DashboardService $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Display dashboard
     */
    public function index(Request $request)
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        if ($user?->isSuperAdmin()) {
            return $this->superAdminDashboard($request);
        }

        $companyId = $user?->company_id;
        $range = $request->query('range', 'this_year');
        $group = $request->query('group', 'monthly');
        $dateRange = $this->dashboardService->resolveDateRange($range);

        if ($request->ajax()) {
            return $this->getDashboardData($companyId, $range, $group);
        }

        $statistics = $this->dashboardService->getStatistics($companyId, $dateRange['start'], $dateRange['end']);
        $recentTransactions = $this->dashboardService->getRecentTransactions($companyId, 5);
        $chartData = $this->dashboardService->getChartData($companyId, $group, $dateRange['start'], $dateRange['end']);
        $receivablesTrend = $this->dashboardService->getReceivablesTrend($companyId, $dateRange['start'], $dateRange['end']);
        $payablesTrend = $this->dashboardService->getPayablesTrend($companyId, $dateRange['start'], $dateRange['end']);

        return view('admin.dashboard', compact(
            'statistics',
            'recentTransactions',
            'chartData',
            'receivablesTrend',
            'payablesTrend',
            'range',
            'group'
        ));
    }

    /**
     * Display platform dashboard for super admin.
     */
    protected function superAdminDashboard(Request $request)
    {
        $statistics = $this->dashboardService->getPlatformStatistics();
        $recentRegistrations = $this->dashboardService->getRecentCompanyRegistrations();
        $recentPayments = $this->dashboardService->getRecentPlatformPayments();
        $expiryAlerts = $this->dashboardService->getSubscriptionExpiryAlerts();

        if ($request->ajax()) {
            return response()->json([
                'statistics' => $statistics,
                'recent_registrations' => $recentRegistrations,
                'recent_payments' => $recentPayments,
                'expiry_alerts' => $expiryAlerts,
            ]);
        }

        return view('admin.dashboard-superadmin', compact('statistics', 'recentRegistrations', 'recentPayments', 'expiryAlerts'));
    }

    /**
     * Get dashboard data via AJAX
     */
    protected function getDashboardData(int $companyId, string $range = 'this_year', string $group = 'monthly'): JsonResponse
    {
        $dateRange = $this->dashboardService->resolveDateRange($range);
        $statistics = $this->dashboardService->getStatistics($companyId, $dateRange['start'], $dateRange['end']);
        $recentTransactions = $this->dashboardService->getRecentTransactions($companyId, 10);
        $chartData = $this->dashboardService->getChartData($companyId, $group, $dateRange['start'], $dateRange['end']);
        $receivablesTrend = $this->dashboardService->getReceivablesTrend($companyId, $dateRange['start'], $dateRange['end']);
        $payablesTrend = $this->dashboardService->getPayablesTrend($companyId, $dateRange['start'], $dateRange['end']);

        return response()->json([
            'statistics' => $statistics,
            'recent_transactions' => $recentTransactions,
            'chart_data' => $chartData,
            'receivables_trend' => $receivablesTrend,
            'payables_trend' => $payablesTrend,
        ]);
    }
}
