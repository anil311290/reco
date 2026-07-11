<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Services\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncApiController extends Controller
{
    protected SyncService $syncService;

    public function __construct(SyncService $syncService)
    {
        $this->syncService = $syncService;
    }

    /**
     * Queue offline changes from mobile (batch upload).
     */
    public function upload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'nullable|string|max:100',
            'entries' => 'required|array|min:1',
            'entries.*.table_name' => 'required|string',
            'entries.*.record_uuid' => 'required|uuid',
            'entries.*.operation' => 'required|in:create,update,delete',
            'entries.*.payload' => 'nullable|array',
            'entries.*.local_version' => 'nullable|integer|min:0',
            'auto_process' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $queued = $this->syncService->queueUpload(
            $validated['entries'],
            $user->id,
            $user->company_id,
            $validated['device_id'] ?? null
        );

        $processed = [];
        if ($request->boolean('auto_process', true)) {
            $processed = $this->syncService->processPending(
                $user->company_id,
                $user->id,
                $validated['device_id'] ?? null
            );
        }

        return ResponseHelper::success([
            'queued' => count($queued),
            'processed' => $processed,
        ], 'Sync upload received');
    }

    /**
     * Manual sync button — process all pending queue items.
     */
    public function run(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'nullable|string|max:100',
        ]);

        $user = $request->user();
        $processed = $this->syncService->processPending(
            $user->company_id,
            $user->id,
            $validated['device_id'] ?? null
        );

        return ResponseHelper::success([
            'processed' => $processed,
            'status' => $this->syncService->getStatus($user->company_id, $validated['device_id'] ?? null),
        ], 'Manual sync completed');
    }

    /**
     * Download server changes since timestamp.
     */
    public function download(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'since' => 'nullable|date',
            'tables' => 'nullable|array',
            'tables.*' => 'string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:500',
        ]);

        $data = $this->syncService->download(
            $request->user()->company_id,
            $validated['since'] ?? null,
            $validated['tables'] ?? null,
            (int) ($validated['page'] ?? 1),
            (int) ($validated['per_page'] ?? 100)
        );

        return ResponseHelper::success($data);
    }

    /**
     * Initial full data load for offline app.
     */
    public function bootstrap(Request $request): JsonResponse
    {
        return ResponseHelper::success(
            $this->syncService->bootstrap($request->user()->company_id)
        );
    }

    /**
     * Sync queue health / pending counts.
     */
    public function status(Request $request): JsonResponse
    {
        $deviceId = $request->input('device_id');

        return ResponseHelper::success([
            'tables' => $this->syncService->getSyncableTables(),
            'status' => $this->syncService->getStatus($request->user()->company_id, $deviceId),
        ]);
    }
}
