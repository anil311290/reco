<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Services\ReportService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardApiController extends Controller
{
    protected DashboardService $dashboardService;
    protected ReportService $reportService;

    public function __construct(DashboardService $dashboardService, ReportService $reportService)
    {
        $this->dashboardService = $dashboardService;
        $this->reportService = $reportService;
    }

    /**
     * Get dashboard data
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $validated = $request->validate([
            'range' => 'nullable|in:this_month,last_month,this_quarter,this_year',
            'group' => 'nullable|in:monthly,quarterly,yearly',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);
        $range = $validated['range'] ?? 'this_year';
        $group = $validated['group'] ?? 'monthly';
        $limit = (int) ($validated['limit'] ?? 10);
        $dateRange = $this->dashboardService->resolveDateRange($range);

        $statistics = $this->dashboardService->getStatistics(
            $companyId,
            $dateRange['start'],
            $dateRange['end']
        );
        $recentTransactions = $this->dashboardService->getRecentTransactions($companyId, $limit);
        $chartData = $this->dashboardService->getChartData(
            $companyId,
            $group,
            $dateRange['start'],
            $dateRange['end']
        );
        $receivablesTrend = $this->dashboardService->getReceivablesTrend(
            $companyId,
            $dateRange['start'],
            $dateRange['end']
        );
        $payablesTrend = $this->dashboardService->getPayablesTrend(
            $companyId,
            $dateRange['start'],
            $dateRange['end']
        );

        return ResponseHelper::success([
            'statistics' => $statistics,
            'recent_transactions' => $recentTransactions,
            'chart_data' => $chartData,
            'receivables_trend' => $receivablesTrend,
            'payables_trend' => $payablesTrend,
            'range' => $range,
            'group' => $group,
        ]);
    }

    /**
     * Get monthly data for charts
     */
    public function monthlyData(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $year = $request->input('year', date('Y'));

        $data = $this->dashboardService->getMonthlyData($companyId, $year);

        return ResponseHelper::success($data);
    }

    /**
     * Get receivables trend
     */
    public function receivablesTrend(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $months = $request->input('months', 6);

        $data = $this->dashboardService->getReceivablesTrend($companyId, $months);

        return ResponseHelper::success($data);
    }

    /**
     * Get payables trend
     */
    public function payablesTrend(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $months = $request->input('months', 6);

        $data = $this->dashboardService->getPayablesTrend($companyId, $months);

        return ResponseHelper::success($data);
    }
}
