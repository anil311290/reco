<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\LoginHistoryService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        } catch (ValidationException $e) {
            return ResponseHelper::validationError($e->errors(), collect($e->errors())->flatten()->first() ?? 'Validation Error');
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
                'Registration successful. Your account is pending admin approval.',
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
            'phone' => 'sometimes|string|max:20',
        ]);

        $user = $this->authService->updateProfile($request->user(), $request->only([
            'name', 'phone',
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
            'current_password' => 'required|string|current_password',
            'new_password' => 'required_without:password|string|min:8|confirmed|different:current_password',
            'password' => 'required_without:new_password|string|min:8|confirmed|different:current_password',
        ]);

        $newPassword = $request->input('new_password', $request->input('password'));

        $this->authService->changePassword(
            $request->user(),
            $request->current_password,
            $newPassword
        );

        return ResponseHelper::success(null, 'Password changed successfully');
    }

    /**
     * Send a password reset link to the given email.
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return ResponseHelper::success(null, __($status));
        }

        return ResponseHelper::error(__($status), 400);
    }

    /**
     * Reset the password using the emailed token.
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return ResponseHelper::success(null, __($status));
        }

        return ResponseHelper::error(__($status), 400);
    }
}
