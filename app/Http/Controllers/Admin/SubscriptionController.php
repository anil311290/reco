<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Services\SubscriptionService;
use App\Helpers\ResponseHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Display subscription plans.
     */
    public function plans()
    {
        $plans = $this->subscriptionService->getPlans();
        $companyId = Auth::user()?->company_id;
        $currentSubscription = $this->subscriptionService->getActiveSubscription($companyId);

        return view('admin.subscriptions.plans', compact('plans', 'currentSubscription'));
    }

    /**
     * Show current subscription.
     */
    public function current()
    {
        $companyId = Auth::user()?->company_id;
        $subscription = $this->subscriptionService->getActiveSubscription($companyId);

        return view('admin.subscriptions.current', compact('subscription'));
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

        try {
            $companyId = Auth::user()?->company_id;
            $subscription = $this->subscriptionService->subscribe(
                $companyId,
                $validated['plan_id'],
                $validated['billing_cycle']
            );

            return ResponseHelper::success($subscription, 'Subscription created successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
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

        try {
            $companyId = Auth::user()?->company_id;
            $subscription = $this->subscriptionService->changePlan(
                $companyId,
                $validated['plan_id'],
                $validated['billing_cycle']
            );

            return ResponseHelper::success($subscription, 'Plan changed successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * Cancel subscription.
     */
    public function cancel(): JsonResponse
    {
        try {
            $companyId = Auth::user()?->company_id;
            $this->subscriptionService->cancelActiveSubscription($companyId);
            return ResponseHelper::success(null, 'Subscription cancelled successfully');
        } catch (\Exception $e) {
            return ResponseHelper::error($e->getMessage());
        }
    }

    /**
     * View subscription invoices.
     */
    public function invoices(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        $perPage = $request->input('per_page', 15);
        $invoices = $this->subscriptionService->getInvoices($companyId, $perPage);

        return view('admin.subscriptions.invoices', compact('invoices'));
    }

    /**
     * View subscription payments.
     */
    public function payments(Request $request)
    {
        $companyId = Auth::user()?->company_id;
        $perPage = $request->input('per_page', 15);
        $payments = $this->subscriptionService->getPayments($companyId, $perPage);

        return view('admin.subscriptions.payments', compact('payments'));
    }

    /**
     * Platform-wide subscriptions list for super admin.
     */
    public function platformIndex(Request $request)
    {
        if ($request->ajax()) {
            $query = Subscription::query()
                ->with([
                    'company' => function ($q) {
                        $q->withTrashed()->select('id', 'name');
                    },
                    'plan:id,name',
                ])
                ->latest('created_at');

            return DataTables::eloquent($query)
                ->addColumn('company_name', function (Subscription $subscription) {
                    return $subscription->company->name ?? 'Unknown Company';
                })
                ->addColumn('plan_name', function (Subscription $subscription) {
                    return $subscription->plan->name ?? 'Unknown Plan';
                })
                ->addColumn('status_badge', function (Subscription $subscription) {
                    return '<span class="badge text-bg-light border">' . ucfirst((string) $subscription->status) . '</span>';
                })
                ->editColumn('billing_cycle', function (Subscription $subscription) {
                    return ucfirst((string) $subscription->billing_cycle);
                })
                ->addColumn('amount_formatted', function (Subscription $subscription) {
                    return '₹' . number_format((float) $subscription->amount, 2);
                })
                ->addColumn('period_end_formatted', function (Subscription $subscription) {
                    return optional($subscription->current_period_end)->format('d M Y') ?: '-';
                })
                ->addColumn('created_at_formatted', function (Subscription $subscription) {
                    return optional($subscription->created_at)->format('d M Y') ?: '-';
                })
                ->addColumn('actions', function (Subscription $subscription) {
                    $companyUrl = route('admin.companies.show', $subscription->company_id);
                    $paymentsUrl = route('admin.platform-subscriptions.payments', ['company_id' => $subscription->company_id]);

                    return '<div class="d-flex justify-content-end gap-2">'
                        . '<a href="' . $companyUrl . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-building me-1"></i>Company</a>'
                        . '<a href="' . $paymentsUrl . '" class="btn btn-sm btn-outline-secondary"><i class="bi bi-cash-coin me-1"></i>Payments</a>'
                        . '</div>';
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        return view('admin.subscriptions.platform-index');
    }

    /**
     * Platform-wide subscription payments for super admin.
     */
    public function platformPayments(Request $request)
    {
        $companyId = $request->integer('company_id');

        if ($request->ajax()) {
            $query = SubscriptionPayment::query()
                ->with([
                    'company' => function ($q) {
                        $q->withTrashed()->select('id', 'name');
                    },
                    'subscription.plan:id,name',
                    'invoice',
                ])
                ->when($companyId, function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->latest('created_at');

            return DataTables::eloquent($query)
                ->addColumn('company_name', function (SubscriptionPayment $payment) {
                    return $payment->company->name ?? 'Unknown Company';
                })
                ->addColumn('plan_name', function (SubscriptionPayment $payment) {
                    return $payment->subscription->plan->name ?? 'Unknown Plan';
                })
                ->addColumn('amount_formatted', function (SubscriptionPayment $payment) {
                    return '₹' . number_format((float) $payment->amount, 2);
                })
                ->addColumn('status_badge', function (SubscriptionPayment $payment) {
                    return '<span class="badge text-bg-light border">' . ucfirst((string) $payment->status) . '</span>';
                })
                ->editColumn('payment_method', function (SubscriptionPayment $payment) {
                    return ucfirst((string) ($payment->payment_method ?? 'online'));
                })
                ->addColumn('razorpay_payment_id_formatted', function (SubscriptionPayment $payment) {
                    return $payment->razorpay_payment_id ?: '-';
                })
                ->addColumn('paid_at_formatted', function (SubscriptionPayment $payment) {
                    return optional($payment->paid_at ?? $payment->created_at)->format('d M Y h:i A') ?: '-';
                })
                ->addColumn('actions', function (SubscriptionPayment $payment) {
                    $companyUrl = route('admin.companies.show', $payment->company_id);
                    return '<a href="' . $companyUrl . '" class="btn btn-sm btn-outline-primary"><i class="bi bi-building me-1"></i>Company</a>';
                })
                ->rawColumns(['status_badge', 'actions'])
                ->make(true);
        }

        $selectedCompany = null;
        if ($companyId) {
            $selectedCompany = \App\Models\Company::query()->find($companyId);
        }

        return view('admin.subscriptions.platform-payments', compact('selectedCompany'));
    }
}
