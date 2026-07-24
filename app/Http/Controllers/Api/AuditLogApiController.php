<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogApiController extends Controller
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage = max(1, min((int) $request->input('per_page', 25), 100));

        $filters = array_filter([
            'company_id' => $companyId,
            'action' => $request->input('action'),
            'module' => $request->input('module'),
            'user_id' => $request->input('user_id'),
            'record_id' => $request->input('record_id'),
            'search' => $request->input('search'),
        ], fn ($value) => $value !== null && $value !== '');

        $logs = $this->auditLogService->getPaginated($filters, $perPage);

        return ResponseHelper::success([
            'logs' => $logs->items(),
            'pagination' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
                'has_more' => $logs->hasMorePages(),
            ],
            'statistics' => $this->auditLogService->getStatistics($companyId),
            'filters' => $this->auditLogService->getFilterOptions($companyId),
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $log = $this->auditLogService->getById($id);

        if (!$log || $log->company_id !== $request->user()->company_id) {
            return ResponseHelper::notFound('Audit log not found');
        }

        return ResponseHelper::success($log);
    }
}
