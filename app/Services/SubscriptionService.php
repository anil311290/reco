<?php

namespace App\Services;

use App\Models\RazorpayOrder;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPayment;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SubscriptionService
{
    public function __construct(
        protected RazorpayService $razorpayService
    ) {
    }
    /**
     * Get all plans.
     */
    public function getPlans(bool $activeOnly = true, bool $visibleOnly = false): Collection
    {
        $query = SubscriptionPlan::query();
        if ($activeOnly) {
            $query->active();
        }
        if ($visibleOnly) {
            $query->visible();
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
     * Subscribe or initiate Razorpay checkout when payment is required.
     */
    public function subscribeWithPayment(
        int $companyId,
        int $planId,
        string $billingCycle = 'monthly',
        ?string $ip = null,
        ?array $userContext = null
    ): array {
        $plan = SubscriptionPlan::findOrFail($planId);
        $amount = $plan->getPrice($billingCycle);

        if ($amount <= 0) {
            return [
                'requires_payment' => false,
                'subscription' => $this->subscribe($companyId, $planId, $billingCycle),
            ];
        }

        if (!$this->razorpayService->isConfigured()) {
            throw new RuntimeException('Online payment is not configured. Contact support or try a free plan.');
        }

        $receipt = 'sub_' . $companyId . '_' . time();
        $gatewayOrder = $this->razorpayService->createOrder($amount, $receipt, [
            'company_id' => (string) $companyId,
            'plan_id' => (string) $planId,
            'billing_cycle' => $billingCycle,
        ]);

        $localOrder = RazorpayOrder::create([
            'uuid' => Str::uuid(),
            'company_id' => $companyId,
            'subscription_id' => null,
            'razorpay_order_id' => $gatewayOrder['id'],
            'amount' => $amount,
            'currency' => $gatewayOrder['currency'] ?? 'INR',
            'status' => 'created',
            'notes' => [
                'company_id' => $companyId,
                'plan_id' => $planId,
                'billing_cycle' => $billingCycle,
            ],
            'gateway_response' => $gatewayOrder,
            'receipt' => $receipt,
            'created_by_ip' => $ip,
        ]);

        return [
            'requires_payment' => true,
            'checkout' => [
                'key_id' => $this->razorpayService->getKeyId(),
                'order_id' => $gatewayOrder['id'],
                'amount_paise' => (int) ($gatewayOrder['amount'] ?? ($amount * 100)),
                'currency' => $gatewayOrder['currency'] ?? 'INR',
                'description' => $plan->name . ' — ' . ucfirst($billingCycle) . ' subscription',
                'plan_name' => $plan->name,
                'local_order_id' => $localOrder->id,
                'user_name' => $userContext['name'] ?? '',
                'user_email' => $userContext['email'] ?? '',
            ],
        ];
    }

    /**
     * Verify Razorpay payment and activate subscription.
     */
    public function verifyAndActivatePayment(
        string $razorpayOrderId,
        string $razorpayPaymentId,
        string $signature,
        ?string $ip = null
    ): Subscription {
        if (!$this->razorpayService->verifyPaymentSignature($razorpayOrderId, $razorpayPaymentId, $signature)) {
            throw new RuntimeException('Payment verification failed. Invalid signature.');
        }

        $order = RazorpayOrder::where('razorpay_order_id', $razorpayOrderId)->firstOrFail();

        if ($order->isPaid() && $order->subscription_id) {
            return Subscription::with('plan')->findOrFail($order->subscription_id);
        }

        $payment = $this->razorpayService->fetchPayment($razorpayPaymentId) ?? [];

        return $this->fulfillPaidOrder($order, $razorpayPaymentId, $payment, $ip);
    }

    /**
     * Fulfill a paid Razorpay order (used by verify callback and webhook).
     */
    public function fulfillPaidOrder(
        RazorpayOrder $order,
        string $razorpayPaymentId,
        array $paymentPayload = [],
        ?string $ip = null
    ): Subscription {
        if ($order->isPaid() && $order->subscription_id) {
            return Subscription::with('plan')->findOrFail($order->subscription_id);
        }

        $notes = $order->notes ?? [];
        $companyId = (int) ($notes['company_id'] ?? $order->company_id);
        $planId = (int) ($notes['plan_id'] ?? 0);
        $billingCycle = (string) ($notes['billing_cycle'] ?? 'monthly');

        if (!$companyId || !$planId) {
            throw new RuntimeException('Order is missing subscription metadata.');
        }

        return DB::transaction(function () use ($order, $razorpayPaymentId, $paymentPayload, $ip, $companyId, $planId, $billingCycle) {
            $existingPayment = SubscriptionPayment::where('razorpay_payment_id', $razorpayPaymentId)->first();
            if ($existingPayment && $existingPayment->subscription_id) {
                $order->update(['status' => 'paid', 'subscription_id' => $existingPayment->subscription_id]);
                return Subscription::with('plan')->findOrFail($existingPayment->subscription_id);
            }

            $this->cancelActiveSubscription($companyId);
            $subscription = $this->subscribe($companyId, $planId, $billingCycle);
            $invoice = $this->createPaidInvoice($subscription);
            $amount = isset($paymentPayload['amount']) ? ($paymentPayload['amount'] / 100) : (float) $order->amount;

            SubscriptionPayment::create([
                'uuid' => Str::uuid(),
                'company_id' => $companyId,
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'razorpay_payment_id' => $razorpayPaymentId,
                'razorpay_order_id' => $order->razorpay_order_id,
                'amount' => $amount,
                'currency' => $paymentPayload['currency'] ?? $order->currency ?? 'INR',
                'status' => 'completed',
                'payment_method' => $paymentPayload['method'] ?? 'online',
                'gateway_response' => $paymentPayload ?: null,
                'paid_at' => now(),
                'created_by_ip' => $ip,
                'updated_by_ip' => $ip,
            ]);

            $order->update([
                'status' => 'paid',
                'subscription_id' => $subscription->id,
                'attempts' => ($order->attempts ?? 0) + 1,
            ]);

            // A successful website purchase activates the new tenant immediately.
            Company::whereKey($companyId)->update(['is_active' => true]);
            User::where('company_id', $companyId)->update(['status' => 'active']);

            return $subscription->fresh(['plan']);
        });
    }

    protected function createPaidInvoice(Subscription $subscription): SubscriptionInvoice
    {
        $plan = $subscription->plan;
        $amount = (float) $subscription->amount;

        return SubscriptionInvoice::create([
            'uuid' => Str::uuid(),
            'invoice_number' => $this->generateInvoiceNumber(),
            'company_id' => $subscription->company_id,
            'subscription_id' => $subscription->id,
            'subtotal' => $amount,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total' => $amount,
            'currency' => $subscription->currency ?? 'INR',
            'status' => 'paid',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'paid_at' => now(),
            'line_items' => [[
                'description' => ($plan->name ?? 'Subscription') . ' — ' . ucfirst($subscription->billing_cycle),
                'amount' => $amount,
            ]],
            'notes' => 'Paid via Razorpay',
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $count = SubscriptionInvoice::count() + 1;

        return 'SUB-INV-' . str_pad((string) $count, 6, '0', STR_PAD_LEFT);
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
        $result = $this->subscribeWithPayment($companyId, $newPlanId, $billingCycle);

        if ($result['requires_payment']) {
            throw new RuntimeException('Payment required to change plan. Use checkout flow.');
        }

        return $result['subscription'];
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
