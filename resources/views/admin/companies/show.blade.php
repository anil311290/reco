@extends('layouts.app')

@section('title', 'Company Details')

@section('content')
<div class="container-fluid px-0">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="mb-1">{{ $company->name }}</h2>
            <p class="text-muted mb-0">Tenant overview, subscription health, and activity snapshot.</p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('admin.companies.edit', $company->id) }}" class="btn btn-primary">
                <i class="bi bi-pencil-square me-2"></i>Edit Company
            </a>
            <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-2"></i>Back
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <span class="badge rounded-pill {{ $company->is_active ? 'text-bg-success-subtle text-success' : 'text-bg-danger-subtle text-danger' }} mb-3">
                        {{ $company->is_active ? 'Active Company' : 'Inactive Company' }}
                    </span>
                    <h4 class="mb-2">Profile</h4>
                    <div class="small d-grid gap-3 text-muted">
                        <div><span class="fw-semibold text-dark d-block">Email</span>{{ $company->email ?: '-' }}</div>
                        <div><span class="fw-semibold text-dark d-block">Phone</span>{{ $company->phone ?: '-' }}</div>
                        <div><span class="fw-semibold text-dark d-block">Financial Year</span>{{ $company->financial_year_start ?: '-' }} to {{ $company->financial_year_end ?: '-' }}</div>
                        <div><span class="fw-semibold text-dark d-block">Timezone</span>{{ $company->timezone ?: '-' }}</div>
                        <div><span class="fw-semibold text-dark d-block">Currency</span>{{ $company->currency ?: '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h4 class="mb-3">Owner & Subscription</h4>
                    <div class="small d-grid gap-3 text-muted">
                        <div><span class="fw-semibold text-dark d-block">Owner</span>{{ $owner->name ?? 'N/A' }}</div>
                        <div><span class="fw-semibold text-dark d-block">Owner Email</span>{{ $owner->email ?? 'N/A' }}</div>
                        <div><span class="fw-semibold text-dark d-block">Current Plan</span>{{ $company->activeSubscription?->plan?->name ?? 'No active plan' }}</div>
                        <div><span class="fw-semibold text-dark d-block">Subscription Status</span>{{ ucfirst($company->activeSubscription?->status ?? 'inactive') }}</div>
                        <div><span class="fw-semibold text-dark d-block">Ends On</span>{{ optional($company->activeSubscription?->current_period_end)->format('d M Y') ?: '-' }}</div>
                        <div><span class="fw-semibold text-dark d-block">Theme</span>{{ $company->theme?->name ?? 'Default Theme' }}</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h4 class="mb-3">Usage Snapshot</h4>
                    <div class="row g-3">
                        <div class="col-6"><div class="p-3 rounded-3 bg-light"><div class="text-muted small">Users</div><div class="fs-4 fw-bold">{{ $statistics['user_count'] }}</div></div></div>
                        <div class="col-6"><div class="p-3 rounded-3 bg-light"><div class="text-muted small">Devices</div><div class="fs-4 fw-bold">{{ $statistics['device_count'] }}</div></div></div>
                        <div class="col-6"><div class="p-3 rounded-3 bg-light"><div class="text-muted small">Transactions</div><div class="fs-4 fw-bold">{{ $statistics['transaction_count'] }}</div></div></div>
                        <div class="col-6"><div class="p-3 rounded-3 bg-light"><div class="text-muted small">Logins 30d</div><div class="fs-4 fw-bold">{{ $statistics['login_count_30d'] }}</div></div></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="mb-1">Accounting Summary</h5>
                    <p class="text-muted small mb-0">Read-only high-level business totals.</p>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-3">
                        <div class="col-md-6"><div class="rounded-3 bg-success-subtle p-3"><div class="text-muted small">Sales</div><div class="fs-5 fw-bold">₹{{ number_format($statistics['sales_total'], 2) }}</div></div></div>
                        <div class="col-md-6"><div class="rounded-3 bg-danger-subtle p-3"><div class="text-muted small">Purchases</div><div class="fs-5 fw-bold">₹{{ number_format($statistics['purchase_total'], 2) }}</div></div></div>
                        <div class="col-md-6"><div class="rounded-3 bg-info-subtle p-3"><div class="text-muted small">Receipts</div><div class="fs-5 fw-bold">₹{{ number_format($statistics['receipt_total'], 2) }}</div></div></div>
                        <div class="col-md-6"><div class="rounded-3 bg-warning-subtle p-3"><div class="text-muted small">Payments</div><div class="fs-5 fw-bold">₹{{ number_format($statistics['payment_total'], 2) }}</div></div></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="mb-1">Recent Platform Payments</h5>
                    <p class="text-muted small mb-0">Recent subscription-side payments for this company.</p>
                </div>
                <div class="card-body pt-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentPayments as $payment)
                        <div class="list-group-item border-0 px-0 py-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold">₹{{ number_format($payment->amount, 2) }}</div>
                                    <div class="text-muted small">{{ ucfirst($payment->status) }} · {{ ucfirst($payment->payment_method ?? 'online') }}</div>
                                </div>
                                <div class="text-muted small">{{ optional($payment->paid_at ?? $payment->created_at)->format('d M Y') }}</div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4">No recent subscription payments.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-1">Users</h5></div>
                <div class="card-body pt-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentUsers as $user)
                        <div class="list-group-item border-0 px-0 py-3">
                            <div class="fw-semibold">{{ $user->name }}</div>
                            <div class="text-muted small">{{ $user->email }} · {{ ucfirst($user->role) }}</div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4">No users found.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-1">Login History</h5></div>
                <div class="card-body pt-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentLogins as $login)
                        <div class="list-group-item border-0 px-0 py-3">
                            <div class="fw-semibold">{{ $login->user->name ?? 'Unknown User' }}</div>
                            <div class="text-muted small">{{ ucfirst($login->status) }} · {{ optional($login->created_at)->format('d M Y h:i A') }}</div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4">No login history available.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 pt-4 px-4"><h5 class="mb-1">Device Activity</h5></div>
                <div class="card-body pt-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentDevices as $device)
                        <div class="list-group-item border-0 px-0 py-3">
                            <div class="fw-semibold">{{ $device->device_name ?: $device->device_type }}</div>
                            <div class="text-muted small">{{ $device->user->name ?? 'Unknown User' }} · {{ optional($device->last_active_at)->format('d M Y h:i A') ?: 'Never active' }}</div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4">No device activity available.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection