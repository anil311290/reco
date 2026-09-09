@extends('layouts.app')

@section('title', 'Current Subscription')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Current Subscription</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.subscriptions.plans') }}" class="btn btn-outline-primary me-2">
            <i class="bi bi-grid me-1"></i>View Plans
        </a>
        <a href="{{ route('admin.subscriptions.invoices') }}" class="btn btn-outline-secondary">
            <i class="bi bi-receipt me-1"></i>Invoices
        </a>
    </div>
</div>

@if($subscription)
<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ $subscription->plan->name ?? 'N/A' }} Plan</h5>
                @php
                $statusColors = ['trial'=>'warning','active'=>'success','past_due'=>'danger','cancelled'=>'dark','expired'=>'secondary'];
                @endphp
                <span class="badge bg-{{ $statusColors[$subscription->status] ?? 'secondary' }} fs-6">{{ ucfirst($subscription->status) }}</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted">Billing Cycle</small>
                            <h5 class="mb-0">{{ ucfirst($subscription->billing_cycle) }}</h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted">Amount</small>
                            <h5 class="mb-0">
                                ₹{{ number_format($subscription->amount, 2) }}
                                @if($subscription->billing_cycle === 'lifetime')
                                    (Lifetime)
                                @elseif($subscription->billing_cycle === 'yearly')
                                    /year
                                @else
                                    /month
                                @endif
                            </h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted">Current Period</small>
                            <h5 class="mb-0">{{ $subscription->current_period_start?->format('d-M-Y') }} - {{ $subscription->current_period_end?->format('d-M-Y') }}</h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <small class="text-muted">
                                @if($subscription->isOnTrial())
                                    Trial Ends
                                @elseif($subscription->billing_cycle === 'lifetime')
                                    Purchased
                                @else
                                    Next Renewal
                                @endif
                            </small>
                            <h5 class="mb-0">
                                {{ $subscription->isOnTrial() 
                                    ? $subscription->trial_end_date?->format('d-M-Y') 
                                    : ($subscription->billing_cycle === 'lifetime' 
                                        ? 'One-time purchase' 
                                        : $subscription->current_period_end?->format('d-M-Y')) }}
                            </h5>
                        </div>
                    </div>
                </div>

                @if($subscription->plan)
                <hr>
                <h6>Plan Limits</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="text-center p-2 border rounded">
                            <h4 class="mb-0">{{ $subscription->plan->max_users == -1 ? '∞' : $subscription->plan->max_users }}</h4>
                            <small class="text-muted">Users</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-2 border rounded">
                            <h4 class="mb-0">{{ $subscription->plan->max_transactions == -1 ? '∞' : number_format($subscription->plan->max_transactions) }}</h4>
                            <small class="text-muted">Transactions</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-2 border rounded">
                            <h4 class="mb-0">{{ $subscription->plan->max_accounts == -1 ? '∞' : $subscription->plan->max_accounts }}</h4>
                            <small class="text-muted">Accounts</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-2 border rounded">
                            <h4 class="mb-0">{{ $subscription->plan->max_parties == -1 ? '∞' : $subscription->plan->max_parties }}</h4>
                            <small class="text-muted">Parties</small>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Actions</h6></div>
            <div class="card-body">
                @if($subscription->isOnTrial())
                <div class="alert alert-warning py-2 mb-3">
                    <i class="bi bi-clock me-1"></i>Trial ends {{ $subscription->trial_end_date?->diffForHumans() }}
                </div>
                @endif

                @if($subscription->isExpired() && !$subscription->isOnTrial())
                <div class="alert alert-danger py-2 mb-3">
                    <i class="bi bi-exclamation-octagon me-1"></i>Your plan has expired. Renew now to continue using all features.
                </div>
                @elseif(!$subscription->isOnTrial() && $subscription->billing_cycle !== 'lifetime' && $subscription->current_period_end && $subscription->current_period_end->diffInDays(now()) <= 7)
                <div class="alert alert-warning py-2 mb-3">
                    <i class="bi bi-alarm me-1"></i>Plan renews {{ $subscription->current_period_end->diffForHumans() }}.
                </div>
                @endif

                @if($subscription->billing_cycle !== 'lifetime')
                <button class="btn btn-primary w-100 mb-2" id="renewBtn"
                        data-plan="{{ $subscription->plan_id }}"
                        data-cycle="{{ $subscription->billing_cycle }}">
                    <i class="bi bi-arrow-repeat me-1"></i>Renew Now ({{ ucfirst($subscription->billing_cycle) }})
                </button>
                @endif
                <a href="{{ route('admin.subscriptions.plans') }}" class="btn btn-outline-primary w-100 mb-2">
                    <i class="bi bi-arrow-up-circle me-1"></i>Upgrade / Change Plan
                </a>
                <button class="btn btn-outline-danger w-100" id="cancelBtn">
                    <i class="bi bi-x-circle me-1"></i>Cancel Subscription
                </button>
            </div>
        </div>
    </div>
</div>
@else
<div class="card">
    <div class="card-body text-center py-5">
        <i class="bi bi-box fs-1 text-muted"></i>
        <h5 class="mt-3">No Active Subscription</h5>
        <p class="text-muted">Choose a plan to get started</p>
        <a href="{{ route('admin.subscriptions.plans') }}" class="btn btn-primary">
            <i class="bi bi-grid me-2"></i>View Plans
        </a>
    </div>
</div>
@endif
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

$('#renewBtn').on('click', function() {
    const planId = $(this).data('plan');
    const billingCycle = $(this).data('cycle');
    const $btn = $(this);

    Swal.fire({
        title: 'Renew Subscription?',
        text: 'Your plan will be renewed for another ' + billingCycle + ' period.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Renew Now'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }

        $btn.prop('disabled', true);
        $.ajax({
            url: '{{ route('admin.subscriptions.subscribe') }}',
            type: 'POST',
            data: { plan_id: planId, billing_cycle: billingCycle },
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (r) {
                const data = r.data || r;
                if (data.requires_payment && data.checkout) {
                    openRazorpayCheckout(data.checkout);
                    $btn.prop('disabled', false);
                    return;
                }
                toastr.success(r.message || 'Subscription renewed successfully');
                setTimeout(() => location.reload(), 1000);
            },
            error: function (xhr) {
                $btn.prop('disabled', false);
                toastr.error(xhr.responseJSON?.message || 'Error renewing subscription');
            }
        });
    });
});

$('#cancelBtn').on('click', function() {
    Swal.fire({
        title: 'Cancel Subscription?',
        text: 'Your access will continue until the end of the current billing period.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        confirmButtonText: 'Yes, Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("admin.subscriptions.cancel") }}',
                type: 'POST',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(r) {
                    toastr.success(r.message);
                    setTimeout(() => location.reload(), 1000);
                }
            });
        }
    });
});
</script>
@endsection
