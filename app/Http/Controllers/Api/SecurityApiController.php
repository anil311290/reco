<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\PinLoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SecurityApiController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login with PIN
     */
    public function pinLogin(PinLoginRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user || !Hash::check($request->pin, $user->pin)) {
                return ResponseHelper::error('Invalid PIN', 401);
            }

            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            $token = $user->createToken('pin-auth-token')->plainTextToken;

            return ResponseHelper::success([
                'user' => new UserResource($user),
                'token' => $token,
            ], 'PIN login successful');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    /**
     * Set or update PIN
     */
    public function setPin(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string|min:4|max:6|regex:/^[0-9]+$/',
            'pin_confirmation' => 'required|same:pin',
        ]);

        try {
            $user = $request->user();
            $user->update([
                'pin' => Hash::make($request->pin),
                'has_pin' => true,
            ]);

            return ResponseHelper::success(null, 'PIN set successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    /**
     * Verify PIN
     */
    public function verifyPin(Request $request): JsonResponse
    {
        $request->validate([
            'pin' => 'required|string|min:4|max:6',
        ]);

        $user = $request->user();

        if (!Hash::check($request->pin, $user->pin)) {
            return ResponseHelper::error('Invalid PIN', 401);
        }

        return ResponseHelper::success(null, 'PIN verified successfully');
    }

    /**
     * Enable/Disable app lock
     */
    public function toggleAppLock(Request $request): JsonResponse
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        try {
            $user = $request->user();
            $user->update([
                'app_lock_enabled' => $request->enabled,
            ]);

            $status = $request->enabled ? 'enabled' : 'disabled';
            return ResponseHelper::success(null, "App lock {$status} successfully");
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    /**
     * Get security settings
     */
    public function getSecuritySettings(Request $request): JsonResponse
    {
        $user = $request->user();

        return ResponseHelper::success([
            'has_pin' => $user->has_pin ?? false,
            'app_lock_enabled' => $user->app_lock_enabled ?? false,
            'biometric_enabled' => $user->biometric_enabled ?? false,
            'auto_lock_timeout' => $user->auto_lock_timeout ?? 5, // minutes
        ]);
    }

    /**
     * Update security settings
     */
    public function updateSecuritySettings(Request $request): JsonResponse
    {
        $request->validate([
            'biometric_enabled' => 'sometimes|boolean',
            'auto_lock_timeout' => 'sometimes|integer|min:1|max:60',
        ]);

        try {
            $user = $request->user();
            $user->update($request->only(['biometric_enabled', 'auto_lock_timeout']));

            return ResponseHelper::success(null, 'Security settings updated successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }
}
