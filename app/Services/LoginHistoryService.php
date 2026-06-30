<?php

namespace App\Services;

use App\Models\LoginHistory;
use App\Models\UserDevice;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class LoginHistoryService
{
    /**
     * Record a login attempt.
     */
    public function recordLogin(array $data): LoginHistory
    {
        return LoginHistory::create(array_merge($data, [
            'created_at' => now(),
        ]));
    }

    /**
     * Record a logout.
     */
    public function recordLogout(int $userId, ?string $sessionId = null): void
    {
        $query = LoginHistory::where('user_id', $userId)
            ->whereNull('logged_out_at');

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $query->latest()->update(['logged_out_at' => now()]);
    }

    /**
     * Get login history for a user.
     */
    public function getUserHistory(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return LoginHistory::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get login history for a company.
     */
    public function getCompanyHistory(int $companyId, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = LoginHistory::with('user')
            ->where('company_id', $companyId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Get failed login attempts.
     */
    public function getFailedAttempts(int $companyId, int $hours = 24): Collection
    {
        return LoginHistory::where('company_id', $companyId)
            ->where('status', 'failed')
            ->where('created_at', '>', now()->subHours($hours))
            ->get();
    }

    /**
     * Register or update a user device.
     */
    public function registerDevice(int $userId, int $companyId, array $deviceData): UserDevice
    {
        $device = UserDevice::where('user_id', $userId)
            ->where('device_id', $deviceData['device_id'])
            ->first();

        if ($device) {
            $device->update(array_merge($deviceData, [
                'last_active_at' => now(),
                'is_active' => true,
            ]));
            return $device;
        }

        return UserDevice::create(array_merge($deviceData, [
            'uuid' => Str::uuid(),
            'user_id' => $userId,
            'company_id' => $companyId,
            'last_active_at' => now(),
            'is_active' => true,
        ]));
    }

    /**
     * Get user's active devices.
     */
    public function getUserDevices(int $userId): Collection
    {
        return UserDevice::where('user_id', $userId)
            ->active()
            ->orderBy('last_active_at', 'desc')
            ->get();
    }

    /**
     * Deactivate a device.
     */
    public function deactivateDevice(int $deviceId): bool
    {
        return UserDevice::where('id', $deviceId)->update(['is_active' => false]);
    }
}
