<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\AuthService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    use RegistersUsers;

    protected $redirectTo = '/admin/dashboard';
    protected AuthService $authService;
    protected SubscriptionService $subscriptionService;

    public function __construct(AuthService $authService, SubscriptionService $subscriptionService)
    {
        $this->middleware('guest');
        $this->authService = $authService;
        $this->subscriptionService = $subscriptionService;
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

    /**
     * Handle registration via AJAX.
     * Returns JSON — for paid plans includes Razorpay checkout data.
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'company_name' => ['required', 'string', 'max:255'],
            'plan_slug' => ['required', 'string', 'exists:subscription_plans,slug'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $result = $this->authService->register($validator->validated());

            if (is_array($result) && isset($result['requires_payment'])) {
                // Paid plan — return checkout data, frontend opens Razorpay popup
                return response()->json([
                    'success' => true,
                    'requires_payment' => true,
                    'checkout' => $result['checkout'],
                    'message' => 'Complete payment to activate your account',
                ]);
            }

            // Free/trial plan — registration complete
            return response()->json([
                'success' => true,
                'requires_payment' => false,
                'message' => 'Account created successfully. Check your email for next steps.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Verify Razorpay payment after successful checkout (website flow).
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        try {
            $subscription = $this->subscriptionService->verifyAndActivatePayment(
                $validated['razorpay_order_id'],
                $validated['razorpay_payment_id'],
                $validated['razorpay_signature'],
                $request->ip()
            );

            return response()->json([
                'success' => true,
                'message' => 'Payment successful! Your account is being activated.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
