# Reco Backend — Forgot Password API Changes

> **Date:** 2026-07-25
> **For:** Backend Developer
> **Purpose:** Add Forgot Password & Reset Password API endpoints for mobile app

---

## 📁 File 1: `app/Http/Controllers/Api/AuthController.php`

### Add these 2 imports at the top:

```php
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
```

### Add these 2 methods BEFORE the closing `}` of the class:

```php
/**
 * Send password reset link email (Forgot Password)
 */
public function forgotPassword(Request $request): JsonResponse
{
    $request->validate([
        'email' => 'required|email',
    ]);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    if ($status === Password::RESET_LINK_SENT) {
        return ResponseHelper::success(null, __($status));
    }

    return ResponseHelper::error(__($status), 400);
}

/**
 * Reset password with token
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
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->save();
        }
    );

    if ($status === Password::PASSWORD_RESET) {
        return ResponseHelper::success(null, __($status));
    }

    return ResponseHelper::error(__($status), 400);
}
```

---

## 📁 File 2: `routes/api.php`

### In the public routes section (inside `Route::prefix('v1')` group, after register route), add:

```php
// Forgot / Reset Password (public)
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
```

Full public routes block should look like:

```php
Route::prefix('v1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // Forgot / Reset Password (public)
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    Route::get('/states', [StatesCitiesApiController::class, 'states']);
    Route::get('/states/{stateId}/cities', [StatesCitiesApiController::class, 'cities']);
});
```

---

## 📋 API Spec

### 1. Forgot Password
```
POST /api/v1/forgot-password
Body: { "email": "user@example.com" }

Success: { "success": true, "message": "We have emailed your password reset link.", "data": null }
Error:   { "success": false, "message": "We can't find a user with that email address." }
```

### 2. Reset Password
```
POST /api/v1/reset-password
Body: {
    "email": "user@example.com",
    "token": "abc123...",
    "password": "newpassword",
    "password_confirmation": "newpassword"
}

Success: { "success": true, "message": "Your password has been reset.", "data": null }
Error:   { "success": false, "message": "This password reset token is invalid." }
```

---

## ✅ Prerequisites

- Laravel's default `password_reset_tokens` table must exist (comes with default Laravel migration)
- Mail configuration must be set up in `.env` (MAIL_MAILER, etc.) for sending reset emails
- No new migrations needed — uses Laravel's built-in password reset system

---

## 📱 Flutter Side (Already Implemented)

Flutter app already has:
- `api_endpoints.dart` → `forgotPassword` & `resetPassword` endpoints
- `auth_repository.dart` → `forgotPassword()` & `resetPassword()` methods
- `ForgotPasswordController` + `ForgotPasswordScreen` (2-step UI)
- "Forgot Password?" link on Login screen

Once backend deploys these API changes, Flutter will work without any further changes.
