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

        $statistics = $this->dashboardService->getStatistics($companyId);
        $recentTransactions = $this->dashboardService->getRecentTransactions($companyId, 10);

        return ResponseHelper::success([
            'statistics' => $statistics,
            'recent_transactions' => $recentTransactions,
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
