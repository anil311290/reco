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

        $companyId = $request->user()->company_id;
        $subscription = $this->subscriptionService->subscribe(
            $companyId,
            $validated['plan_id'],
            $validated['billing_cycle']
        );

        return ResponseHelper::success(new SubscriptionResource($subscription), 'Subscription created', 201);
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

        $companyId = $request->user()->company_id;
        $subscription = $this->subscriptionService->changePlan(
            $companyId,
            $validated['plan_id'],
            $validated['billing_cycle']
        );

        return ResponseHelper::success(new SubscriptionResource($subscription), 'Plan changed');
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
}
