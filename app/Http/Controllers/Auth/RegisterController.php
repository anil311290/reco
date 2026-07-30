<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\AuthService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/admin/dashboard';
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->middleware('guest');
        $this->authService = $authService;
    }

    public function showRegistrationForm()
    {
        $plans = SubscriptionPlan::query()
            ->active()
            ->visible()
            ->orderBy('sort_order')
            ->get();

        return view('auth.register', compact('plans'));
    }

    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['required', 'string', 'max:255'],
            'plan_slug' => ['required', 'string', 'exists:subscription_plans,slug'],
        ]);
    }

    protected function create(array $data)
    {
        return $this->authService->register($data);
    }

    protected function registered(Request $request, $user)
    {
        // New tenants are pending approval — log them out immediately
        // and redirect to login with an informational message
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login')
            ->with('status', 'Your account has been created and is pending admin approval. You will be notified once it is activated.');
    }
}
