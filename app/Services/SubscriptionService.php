<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class SubscriptionService
{
    /**
     * Get all plans.
     */
    public function getPlans(bool $activeOnly = true): Collection
    {
        $query = SubscriptionPlan::query();
        if ($activeOnly) {
            $query->active();
        }
        return $query->orderBy('sort_order')->get();
    }

    /**
     * Get plan by slug.
     */
    public function getPlanBySlug(string $slug): ?SubscriptionPlan
    {
        return SubscriptionPlan::where('slug', $slug)->first();
    }

    /**
     * Get company's active subscription.
     */
    public function getActiveSubscription(int $companyId): ?Subscription
    {
        return Subscription::with('plan')
            ->where('company_id', $companyId)
            ->active()
            ->first();
    }

    /**
     * Create a new subscription for a company.
     */
    public function subscribe(int $companyId, int $planId, string $billingCycle = 'monthly'): Subscription
    {
        $plan = SubscriptionPlan::findOrFail($planId);
        $amount = $plan->getPrice($billingCycle);

        // Cancel any existing active subscription
        $this->cancelActiveSubscription($companyId);

        $startDate = now();

        $periodEnd = match($billingCycle) {
            'yearly' => $startDate->copy()->addYear(),
            'lifetime' => null,
            default => $startDate->copy()->addMonth(),
        };

        return Subscription::create([
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'plan_id' => $planId,
            'status' => $plan->trial_days > 0 ? 'trial' : 'active',
            'billing_cycle' => $billingCycle,
            'start_date' => $startDate,
            'trial_end_date' => $plan->trial_days > 0
                ? $startDate->copy()->addDays($plan->trial_days)
                : null,
            'current_period_start' => $startDate,
            'current_period_end' => $periodEnd,
            'amount' => $amount,
            'currency' => config('app.currency', 'INR'),
        ]);
    }

    /**
     * Cancel active subscription for a company.
     */
    public function cancelActiveSubscription(int $companyId): void
    {
        Subscription::where('company_id', $companyId)
            ->active()
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);
    }

    /**
     * Change subscription plan.
     */
    public function changePlan(int $companyId, int $newPlanId, string $billingCycle = 'monthly'): Subscription
    {
        return $this->subscribe($companyId, $newPlanId, $billingCycle);
    }

    /**
     * Get subscription invoices for a company.
     */
    public function getInvoices(int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return SubscriptionInvoice::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get subscription payments for a company.
     */
    public function getPayments(int $companyId, int $perPage = 15): LengthAwarePaginator
    {
        return SubscriptionPayment::where('company_id', $companyId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Check if company has access to a feature.
     */
    public function hasFeature(int $companyId, string $feature): bool
    {
        $subscription = $this->getActiveSubscription($companyId);
        if (!$subscription || !$subscription->plan) {
            return false;
        }

        $features = $subscription->plan->features ?? [];
        if (in_array('all', $features)) {
            return true;
        }

        return in_array($feature, $features);
    }

    /**
     * Check if company has reached a limit.
     */
    public function hasReachedLimit(int $companyId, string $resource, int $currentCount): bool
    {
        $subscription = $this->getActiveSubscription($companyId);
        if (!$subscription || !$subscription->plan) {
            return true;
        }

        $limitField = 'max_' . $resource;
        $limit = $subscription->plan->$limitField ?? 0;

        return $limit > 0 && $currentCount >= $limit;
    }

    /**
     * Handle expired subscriptions.
     */
    public function handleExpiredSubscriptions(): int
    {
        return Subscription::active()
            ->where('current_period_end', '<', now())
            ->update(['status' => 'expired']);
    }
}
