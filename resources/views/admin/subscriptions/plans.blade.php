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
        <a href="{{ route('admin.subscriptions.invoices') }}" class="btn btn-outline-secondary">
            <i class="bi bi-receipt me-2"></i>Invoices
        </a>
    </div>
</div>

@if($currentSubscription)
<div class="alert alert-info mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <strong>Current Plan:</strong> {{ $currentSubscription->plan->name ?? 'N/A' }} |
            <strong>Status:</strong> {{ ucfirst($currentSubscription->status) }} |
            <strong>Valid Until:</strong> {{ $currentSubscription->current_period_end?->format('d M Y') ?? 'N/A' }}
        </div>
        @if($currentSubscription->isOnTrial())
        <span class="badge bg-warning">Trial</span>
        @endif
    </div>
</div>
@endif

<div class="row g-4">
    @foreach($plans as $plan)
    <div class="col-md-3">
        <div class="card h-100 {{ $currentSubscription && $currentSubscription->plan_id == $plan->id ? 'border-primary' : '' }}">
            <div class="card-body text-center">
                @if($currentSubscription && $currentSubscription->plan_id == $plan->id)
                <span class="badge bg-primary mb-2">Current Plan</span>
                @endif
                <h5 class="card-title">{{ $plan->name }}</h5>
                <p class="text-muted small">{{ $plan->description }}</p>
                <div class="my-3">
                    <span class="display-6 fw-bold">₹{{ number_format($plan->monthly_price) }}</span>
                    <span class="text-muted">/month</span>
                </div>
                @if($plan->yearly_price > 0)
                <p class="text-muted small">₹{{ number_format($plan->yearly_price) }}/year (save {{ round((1 - $plan->yearly_price / ($plan->monthly_price * 12)) * 100) }}%)</p>
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
                <button class="btn btn-primary w-100 subscribe-btn" data-plan="{{ $plan->id }}" data-name="{{ $plan->name }}">
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
<script>
$(document).on('click', '.subscribe-btn', function() {
    let planId = $(this).data('plan');
    let planName = $(this).data('name');

    Swal.fire({
        title: 'Subscribe to ' + planName,
        html: `<p>Select billing cycle:</p>
            <select id="billingCycle" class="form-select">
                <option value="monthly">Monthly</option>
                <option value="yearly">Yearly (Save more!)</option>
                <option value="lifetime">Lifetime (One-time payment)</option>
            </select>`,
        showCancelButton: true,
        confirmButtonText: 'Subscribe',
        preConfirm: () => document.getElementById('billingCycle').value
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '{{ route("admin.subscriptions.subscribe") }}',
                type: 'POST',
                data: { plan_id: planId, billing_cycle: result.value },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function(r) {
                    toastr.success(r.message);
                    setTimeout(() => location.reload(), 1000);
                },
                error: function(xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Error subscribing');
                }
            });
        }
    });
});
</script>
@endsection
