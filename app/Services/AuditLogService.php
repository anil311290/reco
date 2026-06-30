<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AuditLogService
{
    /**
     * Get all audit logs with filters
     */
    public function getAll(array $filters = []): Collection
    {
        $query = AuditLog::with(['user', 'company']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Get paginated audit logs
     */
    public function getPaginated(array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = AuditLog::with(['user']);

        if (isset($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (isset($filters['record_id'])) {
            $query->where('record_id', $filters['record_id']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('module', 'like', '%' . $filters['search'] . '%');
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get audit log by ID
     */
    public function getById(int $id): ?AuditLog
    {
        return AuditLog::with(['user', 'company'])->find($id);
    }

    /**
     * Get audit logs for a specific record
     */
    public function getForRecord(string $module, int $recordId): Collection
    {
        return AuditLog::where('module', $module)
            ->where('record_id', $recordId)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get audit statistics
     */
    public function getStatistics(int $companyId): array
    {
        $today = now()->format('Y-m-d');
        $thisMonth = now()->format('Y-m');

        return [
            'total_logs' => AuditLog::where('company_id', $companyId)->count(),
            'today_logs' => AuditLog::where('company_id', $companyId)
                ->whereDate('created_at', $today)
                ->count(),
            'month_logs' => AuditLog::where('company_id', $companyId)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'by_action' => AuditLog::where('company_id', $companyId)
                ->selectRaw('action, COUNT(*) as count')
                ->groupBy('action')
                ->pluck('count', 'action')
                ->toArray(),
            'by_module' => AuditLog::where('company_id', $companyId)
                ->selectRaw('module, COUNT(*) as count')
                ->groupBy('module')
                ->pluck('count', 'module')
                ->toArray(),
        ];
    }

    /**
     * Clean old audit logs
     */
    public function cleanOldLogs(int $companyId, int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        return AuditLog::where('company_id', $companyId)
            ->where('created_at', '<', $cutoffDate)
            ->delete();
    }
}
