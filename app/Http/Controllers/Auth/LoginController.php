<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/admin/dashboard';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Handle a login request — returns JSON for AJAX.
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->attemptLogin($request)) {
            /** @var \App\Models\User $user */
            $user = Auth::user();

            // Block pending accounts — require admin approval before login
            if ($user->isPending()) {
                $this->guard()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'success' => false,
                    'message' => 'Your account is pending admin approval. You will be notified once it is activated.',
                ], 403);
            }

            // Block inactive / suspended accounts
            if (!$user->isActive()) {
                $this->guard()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been suspended. Please contact the administrator.',
                ], 403);
            }

            // Update last login
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful! Redirecting to dashboard...',
                'redirect' => route('admin.dashboard'),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'The provided credentials do not match our records.',
        ], 401);
    }

    /**
     * Log the user out.
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')->with('success', 'Logged out successfully.');
    }
}
