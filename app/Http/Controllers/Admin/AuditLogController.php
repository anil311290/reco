<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    protected AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Display audit logs list
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $filters = [];
            $filters['company_id'] = auth()->user()->company_id;
            if ($request->filled('action')) $filters['action'] = $request->input('action');
            if ($request->filled('module')) $filters['module'] = $request->input('module');
            if ($request->filled('user_id')) $filters['user_id'] = $request->input('user_id');
            if ($request->filled('record_id')) $filters['record_id'] = $request->input('record_id');
            $searchValue = $request->input('search.value', $request->input('search'));
            if (!empty($searchValue)) $filters['search'] = is_array($searchValue) ? ($searchValue['value'] ?? '') : $searchValue;
            
            $logs = $this->auditLogService->getPaginated($filters);

            return response()->json([
                'data' => $logs->items(),
                'recordsTotal' => $logs->total(),
                'recordsFiltered' => $logs->total(),
                'draw' => $request->input('draw'),
            ]);
        }

        $companyId = auth()->user()->company_id;
        $statistics = $this->auditLogService->getStatistics($companyId);

        return view('admin.audit-logs.index', compact('statistics'));
    }

    /**
     * Show audit log details
     */
    public function show(int $id)
    {
        $log = $this->auditLogService->getById($id);

        if (!$log || $log->company_id !== auth()->user()->company_id) {
            abort(404);
        }

        return view('admin.audit-logs.show', compact('log'));
    }
}
