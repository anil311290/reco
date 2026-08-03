@extends('layouts.app')

@section('title', 'Platform Dashboard')

@section('content')
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-body p-4 p-lg-5 platform-hero">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge rounded-pill platform-hero-badge mb-3">Super Admin Portal</span>
                    <h2 class="mb-2 platform-hero-title">Platform Dashboard</h2>
                    <p class="mb-0 platform-hero-subtitle">Monitor tenants, subscriptions, revenue, and subscription health without entering any company accounting workflow.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('admin.companies.index') }}" class="btn platform-hero-btn fw-semibold">
                        <i class="bi bi-buildings me-2"></i>Manage Companies
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        @php
            $cards = [
                ['label' => 'Total Companies', 'value' => $statistics['total_companies'], 'icon' => 'bi-buildings'],
                ['label' => 'Active Companies', 'value' => $statistics['active_companies'], 'icon' => 'bi-building-check'],
                ['label' => 'Inactive Companies', 'value' => $statistics['inactive_companies'], 'icon' => 'bi-building-x'],
                ['label' => 'Trial Companies', 'value' => $statistics['trial_companies'], 'icon' => 'bi-hourglass-split'],
                ['label' => 'Expired Companies', 'value' => $statistics['expired_companies'], 'icon' => 'bi-exclamation-octagon'],
                ['label' => 'Active Users', 'value' => $statistics['active_users'], 'icon' => 'bi-people'],
                ['label' => 'Monthly Revenue', 'value' => '₹' . number_format($statistics['monthly_revenue'], 2), 'icon' => 'bi-currency-rupee'],
                ['label' => 'Yearly Revenue', 'value' => '₹' . number_format($statistics['yearly_revenue'], 2), 'icon' => 'bi-graph-up-arrow'],
            ];
        @endphp
        @foreach($cards as $card)
        <div class="col-xxl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 platform-stat-card">
                <div class="card-body d-flex align-items-center gap-3">
                    <div class="rounded-3 d-inline-flex align-items-center justify-content-center platform-stat-icon" style="width: 52px; height: 52px; font-size: 1.25rem;">
                        <i class="bi {{ $card['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="small text-uppercase fw-semibold platform-stat-label">{{ $card['label'] }}</div>
                        <div class="fs-4 fw-bold platform-stat-value">{{ $card['value'] }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-4">
        <div class="col-xl-5">
            <div class="card border-0 shadow-sm h-100 platform-panel-card">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="mb-1">Recent Registrations</h5>
                    <p class="text-muted small mb-0">Newest companies entering the platform.</p>
                </div>
                <div class="card-body pt-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentRegistrations as $company)
                        <a href="{{ route('admin.companies.show', $company->id) }}" class="list-group-item list-group-item-action border-0 px-0 py-3 platform-list-item">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $company->name }}</div>
                                    <div class="text-muted small">{{ optional($company->users->first())->email ?? $company->email ?? 'No owner email' }}</div>
                                </div>
                                <div class="text-end small text-muted">
                                    <div>{{ optional($company->created_at)->format('d-M-Y') }}</div>
                                    <div>{{ $company->is_active ? 'Active' : 'Pending / Inactive' }}</div>
                                </div>
                            </div>
                        </a>
                        @empty
                        <div class="text-center text-muted py-4">No recent registrations.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100 platform-panel-card">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="mb-1">Recent Payments</h5>
                    <p class="text-muted small mb-0">Latest subscription payments across tenants.</p>
                </div>
                <div class="card-body pt-0">
                    <div class="list-group list-group-flush">
                        @forelse($recentPayments as $payment)
                        <div class="list-group-item border-0 px-0 py-3 platform-list-item">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div>
                                    <div class="fw-semibold">{{ $payment->company->name ?? 'Unknown Company' }}</div>
                                    <div class="text-muted small">{{ $payment->subscription->plan->name ?? 'Unknown Plan' }} · {{ ucfirst($payment->status) }}</div>
                                </div>
                                <div class="text-end">
                                    <div class="fw-semibold">₹{{ number_format($payment->amount, 2) }}</div>
                                    <div class="text-muted small">{{ optional($payment->paid_at ?? $payment->created_at)->format('d-M-Y') }}</div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <div class="text-center text-muted py-4">No payment activity yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3">
            <div class="card border-0 shadow-sm h-100 platform-panel-card">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="mb-1">Expiry Alerts</h5>
                    <p class="text-muted small mb-0">Subscriptions ending soon.</p>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex flex-column gap-3">
                        @forelse($expiryAlerts as $subscription)
                        <a href="{{ route('admin.companies.show', $subscription->company_id) }}" class="text-decoration-none border rounded-3 p-3 bg-light-subtle platform-expiry-item">
                            <div class="fw-semibold text-dark">{{ $subscription->company->name ?? 'Unknown Company' }}</div>
                            <div class="text-muted small">{{ $subscription->plan->name ?? 'Plan' }} · {{ ucfirst($subscription->status) }}</div>
                            <div class="small text-danger mt-1">Ends {{ optional($subscription->current_period_end)->format('d-M-Y') }}</div>
                        </a>
                        @empty
                        <div class="text-center text-muted py-4">No subscriptions nearing expiry.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection