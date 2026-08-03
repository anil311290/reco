@extends('layouts.app')

@section('title', 'Subscriptions')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Subscription Plans</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.subscriptions.current') }}" class="btn btn-outline-primary me-2">
            <i class="bi bi-check-circle me-2"></i>Current Plan
        </a>
        <a href="{{ route('admin.subscriptions.invoices') }}" class="btn btn-outline-secondary me-2">
            <i class="bi bi-receipt me-2"></i>Invoices
        </a>
        <a href="{{ route('admin.subscriptions.payments') }}" class="btn btn-outline-secondary">
            <i class="bi bi-credit-card me-2"></i>Payments
        </a>
    </div>
</div>

@if(!$razorpayConfigured)
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    Razorpay keys are not configured. Free/trial plans work; paid plans need <code>RAZORPAY_KEY_ID</code> and <code>RAZORPAY_KEY_SECRET</code> in <code>.env</code>.
</div>
@endif

@if($currentSubscription)
<div class="alert alert-info mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong>Current Plan:</strong> {{ $currentSubscription->plan->name ?? 'N/A' }} |
            <strong>Status:</strong> {{ ucfirst($currentSubscription->status) }} |
            <strong>Valid Until:</strong> {{ $currentSubscription->current_period_end?->format('d-M-Y') ?? 'N/A' }}
        </div>
        @if($currentSubscription->isOnTrial())
        <span class="badge bg-warning">Trial</span>
        @endif
    </div>
</div>
@endif

<div class="row g-4">
    @foreach($plans as $plan)
    @php
        $monthly = (float) $plan->monthly_price;
        $yearly = (float) $plan->yearly_price;
        $lifetime = (float) $plan->lifetime_price;
        $isFree = $monthly <= 0 && $yearly <= 0 && $lifetime <= 0;
    @endphp
    <div class="col-md-3">
        <div class="card h-100 {{ $currentSubscription && $currentSubscription->plan_id == $plan->id ? 'border-primary' : '' }}">
            <div class="card-body text-center">
                @if($currentSubscription && $currentSubscription->plan_id == $plan->id)
                <span class="badge bg-primary mb-2">Current Plan</span>
                @endif
                <h5 class="card-title">{{ $plan->name }}</h5>
                <p class="text-muted small">{{ $plan->description }}</p>
                <div class="my-3">
                    @if($isFree)
                    <span class="display-6 fw-bold">Free</span>
                    @else
                    <span class="display-6 fw-bold">₹{{ number_format($monthly) }}</span>
                    <span class="text-muted">/month</span>
                    @endif
                </div>
                @if($yearly > 0 && $monthly > 0)
                <p class="text-muted small">₹{{ number_format($yearly) }}/year</p>
                @endif
                @if($lifetime > 0)
                <p class="text-muted small">₹{{ number_format($lifetime) }} lifetime</p>
                @endif
                <ul class="list-unstyled text-start small">
                    <li class="mb-1"><i class="bi bi-check text-success me-2"></i>{{ $plan->max_users == -1 ? 'Unlimited' : $plan->max_users }} Users</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-2"></i>{{ $plan->max_transactions == -1 ? 'Unlimited' : number_format($plan->max_transactions) }} Transactions</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-2"></i>{{ $plan->max_accounts == -1 ? 'Unlimited' : $plan->max_accounts }} Accounts</li>
                    <li class="mb-1"><i class="bi bi-check text-success me-2"></i>{{ $plan->max_parties == -1 ? 'Unlimited' : $plan->max_parties }} Parties</li>
                    @if($plan->trial_days > 0)
                    <li class="mb-1"><i class="bi bi-check text-success me-2"></i>{{ $plan->trial_days }}-day free trial</li>
                    @endif
                </ul>
                @if(!$currentSubscription || $currentSubscription->plan_id != $plan->id)
                <button class="btn btn-primary w-100 subscribe-btn"
                        data-plan="{{ $plan->id }}"
                        data-name="{{ $plan->name }}"
                        data-free="{{ $isFree ? '1' : '0' }}">
                    {{ $currentSubscription ? 'Switch Plan' : 'Subscribe' }}
                </button>
                @endif
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection

@section('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
function openRazorpayCheckout(checkout) {
    const options = {
        key: checkout.key_id,
        amount: checkout.amount_paise,
        currency: checkout.currency || 'INR',
        name: '{{ config('app.name', 'Reco') }}',
        description: checkout.description,
        order_id: checkout.order_id,
        prefill: {
            name: checkout.user_name || '',
            email: checkout.user_email || ''
        },
        theme: { color: '#1f6feb' },
        handler: function (response) {
            $.ajax({
                url: '{{ route('admin.subscriptions.verify-payment') }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                data: {
                    razorpay_order_id: response.razorpay_order_id,
                    razorpay_payment_id: response.razorpay_payment_id,
                    razorpay_signature: response.razorpay_signature
                },
                success: function (r) {
                    toastr.success(r.message || 'Payment successful');
                    setTimeout(() => location.reload(), 1200);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Payment verification failed');
                }
            });
        },
        modal: {
            ondismiss: function () {
                toastr.info('Payment cancelled');
            }
        }
    };

    const rzp = new Razorpay(options);
    rzp.on('payment.failed', function (response) {
        toastr.error(response.error?.description || 'Payment failed');
    });
    rzp.open();
}

function subscribeToPlan(planId, billingCycle) {
    $.ajax({
        url: '{{ route('admin.subscriptions.subscribe') }}',
        type: 'POST',
        data: { plan_id: planId, billing_cycle: billingCycle },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (r) {
            const data = r.data || r;
            if (data.requires_payment && data.checkout) {
                openRazorpayCheckout(data.checkout);
                return;
            }
            toastr.success(r.message || 'Subscribed successfully');
            setTimeout(() => location.reload(), 1000);
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error subscribing');
        }
    });
}

$(document).on('click', '.subscribe-btn', function () {
    const planId = $(this).data('plan');
    const planName = $(this).data('name');
    const isFree = String($(this).data('free')) === '1';

    if (isFree) {
        subscribeToPlan(planId, 'monthly');
        return;
    }

    Swal.fire({
        title: 'Subscribe to ' + planName,
        html: `<p>Select billing cycle:</p>
            <select id="billingCycle" class="form-select">
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly</option>
                <option value="lifetime">Lifetime (One-time)</option>
            </select>
            <p class="text-muted small mt-2 mb-0">You will be redirected to Razorpay secure checkout.</p>`,
        showCancelButton: true,
        confirmButtonText: 'Continue to Pay',
        preConfirm: () => document.getElementById('billingCycle').value
    }).then((result) => {
        if (result.isConfirmed) {
            subscribeToPlan(planId, result.value);
        }
    });
});
</script>
@endsection
