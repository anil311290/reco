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
                            <h5 class="mb-0">{{ $subscription->current_period_start?->format('d M Y') }} - {{ $subscription->current_period_end?->format('d M Y') }}</h5>
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
                                    ? $subscription->trial_end_date?->format('d M Y') 
                                    : ($subscription->billing_cycle === 'lifetime' 
                                        ? 'One-time purchase' 
                                        : $subscription->current_period_end?->format('d M Y')) }}
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
<script>
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
