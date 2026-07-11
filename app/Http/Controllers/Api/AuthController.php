<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use App\Services\LoginHistoryService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected AuthService $authService;
    protected LoginHistoryService $loginHistoryService;

    public function __construct(AuthService $authService, LoginHistoryService $loginHistoryService)
    {
        $this->authService = $authService;
        $this->loginHistoryService = $loginHistoryService;
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $result = $this->authService->login($validated);

            if (!empty($validated['device_id']) && $result['user']->company_id) {
                $this->loginHistoryService->registerDevice(
                    $result['user']->id,
                    $result['user']->company_id,
                    [
                        'device_id' => $validated['device_id'],
                        'device_type' => $validated['device_type'] ?? 'android',
                        'device_name' => $validated['device_name'] ?? null,
                        'device_os' => $validated['device_os'] ?? null,
                        'fcm_token' => $validated['fcm_token'] ?? null,
                        'push_token' => $validated['push_token'] ?? null,
                        'created_by_ip' => $request->ip(),
                        'updated_by_ip' => $request->ip(),
                    ]
                );
            }

            return ResponseHelper::success([
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
            ], 'Login successful');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 401);
        }
    }

    /**
     * Register user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());

            return ResponseHelper::success(
                new UserResource($user),
                'Registration successful',
                201
            );
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage(), 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user && $user->currentAccessToken()) {
            $this->authService->logout($user);
        }

        return ResponseHelper::success(null, 'Logged out successfully');
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request): JsonResponse
    {
        return ResponseHelper::success(
            new UserResource($request->user())
        );
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $request->user()->id,
            'phone' => 'sometimes|string|max:20',
        ]);

        $user = $this->authService->updateProfile($request->user(), $request->only([
            'name', 'email', 'phone',
        ]));

        return ResponseHelper::success(
            new UserResource($user),
            'Profile updated successfully'
        );
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $this->authService->changePassword(
            $request->user(),
            $request->current_password,
            $request->password
        );

        return ResponseHelper::success(null, 'Password changed successfully');
    }
}
