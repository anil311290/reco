<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Helpers\ResponseHelper;
use App\Services\LoginHistoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceApiController extends Controller
{
    protected LoginHistoryService $loginHistoryService;

    public function __construct(LoginHistoryService $loginHistoryService)
    {
        $this->loginHistoryService = $loginHistoryService;
    }

    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => 'required|string|max:100',
            'device_type' => 'required|in:web,android,ios',
            'device_name' => 'nullable|string|max:255',
            'device_os' => 'nullable|string|max:100',
            'fcm_token' => 'nullable|string|max:500',
            'push_token' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        $device = $this->loginHistoryService->registerDevice($user->id, $user->company_id, [
            'device_id' => $validated['device_id'],
            'device_type' => $validated['device_type'],
            'device_name' => $validated['device_name'] ?? null,
            'device_os' => $validated['device_os'] ?? null,
            'fcm_token' => $validated['fcm_token'] ?? null,
            'push_token' => $validated['push_token'] ?? null,
            'created_by_ip' => $request->ip(),
            'updated_by_ip' => $request->ip(),
        ]);

        return ResponseHelper::success($device, 'Device registered');
    }
}
