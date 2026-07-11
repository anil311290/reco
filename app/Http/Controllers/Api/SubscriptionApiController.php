<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SubscriptionResource;
use App\Http\Resources\SubscriptionPlanResource;
use App\Services\SubscriptionService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionApiController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get all subscription plans.
     */
    public function plans(): JsonResponse
    {
        $plans = $this->subscriptionService->getPlans();
        return ResponseHelper::success(SubscriptionPlanResource::collection($plans));
    }

    /**
     * Get current subscription.
     */
    public function current(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $subscription = $this->subscriptionService->getActiveSubscription($companyId);

        if (!$subscription) {
            return ResponseHelper::success(null, 'No active subscription');
        }

        return ResponseHelper::success(new SubscriptionResource($subscription));
    }

    /**
     * Subscribe to a plan.
     */
    public function subscribe(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
        ]);

        $user = $request->user();
        $result = $this->subscriptionService->subscribeWithPayment(
            $user->company_id,
            $validated['plan_id'],
            $validated['billing_cycle'],
            $request->ip(),
            ['name' => $user->name, 'email' => $user->email]
        );

        if ($result['requires_payment']) {
            return ResponseHelper::success([
                'requires_payment' => true,
                'checkout' => $result['checkout'],
            ], 'Complete payment to activate subscription');
        }

        return ResponseHelper::success(
            new SubscriptionResource($result['subscription']),
            'Subscription created',
            201
        );
    }

    public function verifyPayment(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
        ]);

        $subscription = $this->subscriptionService->verifyAndActivatePayment(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature'],
            $request->ip()
        );

        return ResponseHelper::success(new SubscriptionResource($subscription), 'Payment verified');
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly,lifetime',
        ]);

        $user = $request->user();
        $result = $this->subscriptionService->subscribeWithPayment(
            $user->company_id,
            $validated['plan_id'],
            $validated['billing_cycle'],
            $request->ip(),
            ['name' => $user->name, 'email' => $user->email]
        );

        if ($result['requires_payment']) {
            return ResponseHelper::success([
                'requires_payment' => true,
                'checkout' => $result['checkout'],
            ], 'Complete payment to change plan');
        }

        return ResponseHelper::success(new SubscriptionResource($result['subscription']), 'Plan changed');
    }

    /**
     * Cancel subscription.
     */
    public function cancel(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $this->subscriptionService->cancelActiveSubscription($companyId);

        return ResponseHelper::success(null, 'Subscription cancelled');
    }

    /**
     * Get subscription invoices.
     */
    public function invoices(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage = (int) $request->input('per_page', 15);
        $invoices = $this->subscriptionService->getInvoices($companyId, $perPage);

        return ResponseHelper::success([
            'data' => $invoices->items(),
            'current_page' => $invoices->currentPage(),
            'last_page' => $invoices->lastPage(),
            'per_page' => $invoices->perPage(),
            'total' => $invoices->total(),
        ]);
    }

    /**
     * Get subscription payments.
     */
    public function payments(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage = (int) $request->input('per_page', 15);
        $payments = $this->subscriptionService->getPayments($companyId, $perPage);

        return ResponseHelper::success([
            'data' => $payments->items(),
            'current_page' => $payments->currentPage(),
            'last_page' => $payments->lastPage(),
            'per_page' => $payments->perPage(),
            'total' => $payments->total(),
        ]);
    }
}
