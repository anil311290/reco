@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-modern">
<!-- Page Header -->
<div class="position-relative mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1 fw-bold" style="font-size: 22px; letter-spacing: -0.02em;">Dashboard</h4>
            <p class="text-muted mb-0" style="font-size: 13px;">Welcome back, {{ auth()->user()->name }}. Here's your financial overview.</p>
        </div>
        <div class="d-flex gap-2">
        <select id="dashboardRange" class="form-select form-select-sm" style="width: auto; font-size: 12px;">
            <option value="this_month" {{ ($range ?? 'this_year') === 'this_month' ? 'selected' : '' }}>This Month</option>
            <option value="last_month" {{ ($range ?? 'this_year') === 'last_month' ? 'selected' : '' }}>Last Month</option>
            <option value="this_quarter" {{ ($range ?? 'this_year') === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
            <option value="this_year" {{ ($range ?? 'this_year') === 'this_year' ? 'selected' : '' }}>This Financial Year</option>
        </select>
    </div>
    <div id="dashboardLoader" class="position-absolute top-0 start-0 w-100 h-100 d-none" style="background: rgba(255,255,255,0.7); backdrop-filter: blur(4px); z-index: 10; display: flex; align-items: center; justify-content: center;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
</div>

@php
    $profitValue = (float) ($statistics['profit'] ?? 0);
    $isNetLoss = $profitValue < 0;
    $periodLabel = $statistics['period']['label'] ?? '';
@endphp

<!-- Stats Cards Row 1 -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Total Income</p>
                        <h3 id="statIncome" class="mb-1 fw-bold" style="color: var(--success); letter-spacing: -0.02em;">₹{{ number_format($statistics['income'] ?? 0, 2) }}</h3>
                        <span id="statIncomeDetail" class="d-inline-flex align-items-center gap-1" style="font-size: 12px; color: var(--success);">
                            <i class="bi bi-calendar-check"></i>
                            <span class="text-muted">{{ $periodLabel }}</span>
                        </span>
                    </div>
                    <div class="stats-icon" style="background: var(--success-light); color: var(--success);">
                        <i class="bi bi-arrow-down-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Total Expense</p>
                        <h3 id="statExpense" class="mb-1 fw-bold" style="color: var(--danger); letter-spacing: -0.02em;">₹{{ number_format($statistics['expense'] ?? 0, 2) }}</h3>
                        <span id="statExpenseDetail" class="d-inline-flex align-items-center gap-1" style="font-size: 12px; color: var(--danger);">
                            <i class="bi bi-calendar-check"></i>
                            <span class="text-muted">{{ $periodLabel }}</span>
                        </span>
                    </div>
                    <div class="stats-icon" style="background: var(--danger-light); color: var(--danger);">
                        <i class="bi bi-arrow-up-circle"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p id="statProfitLabel" class="text-muted mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">{{ $isNetLoss ? 'Net Loss' : 'Net Profit' }}</p>
                        <h3 id="statProfit" class="mb-1 fw-bold" style="color: var({{ $isNetLoss ? '--danger' : '--success' }}); letter-spacing: -0.02em;">{{ $isNetLoss ? '-' : '' }}₹{{ number_format(abs($profitValue), 2) }}</h3>
                        <span id="statProfitDetail" class="d-inline-flex align-items-center gap-1" style="font-size: 12px;">
                            <i class="bi bi-graph-up"></i>
                            <span class="text-muted">{{ $periodLabel }}</span>
                        </span>
                    </div>
                    <div class="stats-icon" style="background: var(--primary-light); color: var(--primary);">
                        <i class="bi bi-graph-up-arrow"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card stats-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Cash Balance</p>
                        <h3 id="statCashBalance" class="mb-1 fw-bold" style="color: var(--info); letter-spacing: -0.02em;">₹{{ number_format($statistics['cash_balance'] ?? 0, 2) }}</h3>
                        <span id="statCashBalanceDetail" class="text-muted" style="font-size: 12px;">
                            <i class="bi bi-wallet2"></i> Cash + Bank balance
                        </span>
                    </div>
                    <div class="stats-icon" style="background: var(--info-light); color: var(--info);">
                        <i class="bi bi-wallet2"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards Row 2 -->
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6 d-flex align-items-start">
        <div class="card summary-card-compact w-100" style="border-left: 3px solid var(--warning);">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Receivables</p>
                        <h3 id="statReceivables" class="mb-1 fw-bold" style="color: var(--warning); letter-spacing: -0.02em;">₹{{ number_format($statistics['receivables'] ?? 0, 2) }}</h3>
                        <span id="statReceivablesDetail" class="text-muted" style="font-size: 12px;">Outstanding amount</span>
                    </div>
                    <div class="stats-icon" style="background: var(--warning-light); color: var(--warning);">
                        <i class="bi bi-people"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 d-flex align-items-start">
        <div class="card summary-card-compact w-100" style="border-left: 3px solid var(--danger);">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1" style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;">Payables</p>
                        <h3 id="statPayables" class="mb-1 fw-bold" style="color: var(--danger); letter-spacing: -0.02em;">₹{{ number_format($statistics['payables'] ?? 0, 2) }}</h3>
                        <span id="statPayablesDetail" class="text-muted" style="font-size: 12px;">Outstanding amount</span>
                    </div>
                    <div class="stats-icon" style="background: var(--danger-light); color: var(--danger);">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0" style="font-size: 14px;">
                    <i class="bi bi-lightning-charge me-2" style="color: var(--warning);"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-2 quick-actions-grid">
                    <div class="col-lg-3 col-sm-6">
                        <a href="{{ route('admin.vouchers.create', 'payment') }}" class="quick-action">
                            <i class="bi bi-arrow-up-circle" style="color: var(--warning);"></i>
                            <span>Payment</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <a href="{{ route('admin.vouchers.create', 'receipt') }}" class="quick-action">
                            <i class="bi bi-arrow-down-circle" style="color: var(--info);"></i>
                            <span>Receipt</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <a href="{{ route('admin.vouchers.create', 'journal') }}" class="quick-action">
                            <i class="bi bi-journal-bookmark" style="color: var(--primary);"></i>
                            <span>Adjustment</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <a href="{{ route('admin.sales-invoices.create') }}" class="quick-action">
                            <i class="bi bi-file-earmark-plus" style="color: var(--success);"></i>
                            <span>Sale Invoice</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <a href="{{ route('admin.purchase-invoices.create') }}" class="quick-action">
                            <i class="bi bi-cart-plus" style="color: var(--danger);"></i>
                            <span>Purchase Invoice</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <a href="{{ route('admin.parties.create') }}" class="quick-action">
                            <i class="bi bi-person-plus" style="color: var(--primary);"></i>
                            <span>Add Party</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <a href="{{ route('admin.accounts.create') }}" class="quick-action">
                            <i class="bi bi-bank2" style="color: var(--info);"></i>
                            <span>Add Ledger</span>
                        </a>
                    </div>
                    <div class="col-lg-3 col-sm-6">
                        <a href="{{ route('admin.reports.index') }}" class="quick-action">
                            <i class="bi bi-file-earmark-bar-graph" style="color: var(--secondary);"></i>
                            <span>Reports</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Income vs Expense Chart -->
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0" style="font-size: 14px;">
                    <i class="bi bi-bar-chart me-2" style="color: var(--primary);"></i>Income vs Expense
                </h5>
                <div class="btn-group btn-group-sm" id="dashboardGroupButtons">
                    <button type="button" class="btn btn-outline-secondary {{ ($group ?? 'monthly') === 'monthly' ? 'active' : '' }}" style="font-size: 11px; padding: 4px 12px;" data-group="monthly">Monthly</button>
                    <button type="button" class="btn btn-outline-secondary {{ ($group ?? 'monthly') === 'quarterly' ? 'active' : '' }}" style="font-size: 11px; padding: 4px 12px;" data-group="quarterly">Quarterly</button>
                    <button type="button" class="btn btn-outline-secondary {{ ($group ?? 'monthly') === 'yearly' ? 'active' : '' }}" style="font-size: 11px; padding: 4px 12px;" data-group="yearly">Yearly</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="incomeExpenseChart" height="280"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0" style="font-size: 14px;">
                    <i class="bi bi-clock-history me-2" style="color: var(--secondary);"></i>Recent Transactions
                </h5>
                <a href="{{ route('admin.vouchers.index') }}" class="btn btn-sm btn-link" style="font-size: 12px;">View All</a>
            </div>
            <div class="card-body p-0">
                <div id="recentTransactionsList" class="list-group list-group-flush">
                    @forelse($recentTransactions as $transaction)
                        <a href="{{ route('admin.vouchers.show', $transaction['id']) }}" class="list-group-item list-group-item-action px-3 py-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-semibold">{{ $transaction['voucher_number'] ?? 'Voucher #' . $transaction['id'] }}</div>
                                    <div class="text-muted small">{{ $transaction['party']['name'] ?? 'No party' }} · {{ ucfirst($transaction['voucher_type']) }}</div>
                                </div>
                                <div class="text-end small text-muted">
                                    <div>{{ \Illuminate\Support\Carbon::parse($transaction['voucher_date'])->format('d-M-Y') }}</div>
                                    <div>₹{{ number_format($transaction['total_debit'] ?? 0, 2) }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="list-group-item" style="padding: 24px; text-align: center; border: none;">
                            <div style="color: var(--text-muted); margin-bottom: 12px;">
                                <i class="bi bi-inbox" style="font-size: 36px;"></i>
                            </div>
                            <h6 style="color: var(--text-heading); margin-bottom: 4px;">No transactions yet</h6>
                            <p style="color: var(--text-muted); font-size: 12px; margin: 0;">Start by creating a voucher to see transactions here.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Receivables Trend -->
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0" style="font-size: 14px;">
                    <i class="bi bi-graph-up me-2" style="color: var(--warning);"></i>Receivables Trend
                </h5>
            </div>
            <div class="card-body">
                <canvas id="receivablesChart" height="240"></canvas>
            </div>
        </div>
    </div>

    <!-- Payables Trend -->
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0" style="font-size: 14px;">
                    <i class="bi bi-graph-down me-2" style="color: var(--danger);"></i>Payables Trend
                </h5>
            </div>
            <div class="card-body">
                <canvas id="payablesChart" height="240"></canvas>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('styles')
<style>
    .dashboard-modern {
        display: grid;
        gap: 1.1rem;
    }

    .dashboard-modern .card {
        border: 1px solid color-mix(in srgb, var(--border) 85%, #d7dfef 15%);
        border-radius: 18px;
        box-shadow: 0 10px 28px rgba(15, 23, 42, 0.06);
        overflow: hidden;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .dashboard-modern .card:hover {
        transform: translateY(-3px);
        border-color: color-mix(in srgb, var(--primary) 34%, var(--border) 66%);
        box-shadow: 0 18px 36px rgba(15, 23, 42, 0.1);
    }

    .dashboard-modern .card-header {
        background: linear-gradient(180deg, #fbfcff 0%, #f7faff 100%);
        border-bottom: 1px solid color-mix(in srgb, var(--border-light) 82%, #dbe3f1 18%);
        padding: 0.95rem 1.2rem;
    }

    .dashboard-modern .card-body {
        padding: 1.15rem 1.2rem;
    }

    .dashboard-modern #dashboardRange {
        min-width: 220px;
        height: 42px;
        border-radius: 12px;
        border-color: color-mix(in srgb, var(--border) 84%, #ccd7ea 16%);
        font-size: 0.86rem;
        font-weight: 600;
    }

    .stats-card {
        border: 1px solid var(--border) !important;
        position: relative;
        overflow: hidden;
        transition: all 0.2s ease;
        background: linear-gradient(145deg, #ffffff 0%, #fbfdff 62%, #f4f8ff 100%);
    }
    
    .stats-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, var(--primary) 0%, #a5b4fc 100%);
        opacity: 0;
        transition: opacity 0.2s ease;
    }
    
    .stats-card:hover::before { opacity: 1; }
    .stats-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-lg) !important; }
    
    .stats-icon {
        width: 48px; height: 48px;
        border-radius: var(--radius-md);
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; flex-shrink: 0;
        border: 1px solid rgba(255, 255, 255, 0.38);
        box-shadow: 0 7px 18px rgba(15, 23, 42, 0.08);
    }
    
    .card-title {
        font-weight: 600;
        color: var(--text-heading);
    }
    
    .btn-group .btn {
        font-size: 12px;
        padding: 4px 12px;
    }

    .dashboard-modern .quick-action {
        min-height: 92px;
        border-radius: 14px;
        padding: 0.72rem 0.6rem;
        border-color: color-mix(in srgb, var(--border) 86%, #dce5f5 14%);
        background: linear-gradient(180deg, #ffffff 0%, #f9fbff 100%);
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .dashboard-modern .quick-action:hover {
        transform: translateY(-3px);
        border-color: color-mix(in srgb, var(--primary) 38%, var(--border) 62%);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.09);
    }

    .dashboard-modern .quick-action i {
        font-size: 1.4rem;
        margin-bottom: 0.35rem;
    }

    .dashboard-modern .quick-action span {
        font-size: 0.8rem;
        font-weight: 700;
        letter-spacing: 0.01em;
    }

    .dashboard-modern .quick-actions-grid {
        --bs-gutter-y: 0.6rem;
    }

    .dashboard-modern .quick-actions-grid .col-lg-3 {
        display: flex;
    }

    .dashboard-modern .quick-actions-grid .quick-action {
        width: 100%;
    }

    .dashboard-modern .summary-card-compact {
        height: auto;
        min-height: 0;
    }

    .dashboard-modern .summary-card-compact .card-body {
        padding-bottom: 1rem;
    }

    @media (max-width: 767.98px) {
        .dashboard-modern .quick-action {
            min-height: 88px;
        }

        .dashboard-modern #dashboardRange {
            min-width: 176px;
        }
    }

    body.dark-mode .list-group-item {
        background-color: rgba(255, 255, 255, 0.04) !important;
        border-color: rgba(148, 163, 184, 0.24) !important;
        color: #e2e8f0 !important;
    }

    body.dark-mode .list-group-item .fw-semibold,
    body.dark-mode .list-group-item .text-muted,
    body.dark-mode .list-group-item .small {
        color: rgba(226, 232, 240, 0.92) !important;
    }

    body.dark-mode .dashboard-modern .card {
        background: #151b2c;
        border-color: rgba(148, 163, 184, 0.14);
        box-shadow: 0 16px 34px rgba(2, 6, 23, 0.38);
    }

    body.dark-mode .dashboard-modern .card-header {
        background: rgba(255, 255, 255, 0.03);
        border-bottom-color: rgba(148, 163, 184, 0.12);
    }

    body.dark-mode .stats-card {
        background: linear-gradient(145deg, #161d30 0%, #10192b 100%);
    }

    body.dark-mode .dashboard-modern .quick-action {
        background: linear-gradient(180deg, #141c2f 0%, #0f1729 100%);
        border-color: rgba(148, 163, 184, 0.2);
    }

    body.dark-mode .dashboard-modern #dashboardRange {
        background: #111827;
        color: #e2e8f0;
        border-color: rgba(148, 163, 184, 0.24);
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Dark mode detection for charts
    const isDark = document.body.classList.contains('dark-mode');
    const gridColor = isDark ? 'rgba(255,255,255,0.06)' : 'rgba(0,0,0,0.06)';
    const tickColor = isDark ? '#8492a6' : '#a8aaae';
    const legendColor = isDark ? '#b8c2cc' : '#6e6b7b';

    let incomeExpenseChart = null;
    let receivablesChart = null;
    let payablesChart = null;

    Chart.defaults.color = tickColor;
    Chart.defaults.borderColor = gridColor;

    @php
        $dashboardLabels = $chartData['labels'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $dashboardIncome = $chartData['income'] ?? array_fill(0, count($dashboardLabels), 0);
        $dashboardExpense = $chartData['expense'] ?? array_fill(0, count($dashboardLabels), 0);
        $dashboardReceivablesLabels = $receivablesTrend['labels'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $dashboardReceivablesData = $receivablesTrend['data'] ?? array_fill(0, count($dashboardReceivablesLabels), 0);
        $dashboardPayablesLabels = $payablesTrend['labels'] ?? ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $dashboardPayablesData = $payablesTrend['data'] ?? array_fill(0, count($dashboardPayablesLabels), 0);
    @endphp

    // Income vs Expense Chart
    const incomeExpenseCtx = document.getElementById('incomeExpenseChart').getContext('2d');
    const monthlyLabels = @json($dashboardLabels);
    const monthlyIncome = @json($dashboardIncome);
    const monthlyExpense = @json($dashboardExpense);

    incomeExpenseChart = new Chart(incomeExpenseCtx, {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [
                {
                    label: 'Income',
                    data: monthlyIncome,
                    backgroundColor: 'rgba(40, 199, 111, 0.8)',
                    borderColor: 'rgb(40, 199, 111)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Expense',
                    data: monthlyExpense,
                    backgroundColor: 'rgba(234, 84, 85, 0.8)',
                    borderColor: 'rgb(234, 84, 85)',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, padding: 20, color: legendColor }
                }
            },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: tickColor } },
                y: {
                    beginAtZero: true,
                    grid: { color: gridColor },
                    ticks: { color: tickColor, callback: function(v) { return '₹' + v.toLocaleString(); } }
                }
            }
        }
    });

    // Receivables Chart
    const receivablesCtx = document.getElementById('receivablesChart').getContext('2d');
    const receivablesLabels = @json($dashboardReceivablesLabels);
    const receivablesData = @json($dashboardReceivablesData);

    receivablesChart = new Chart(receivablesCtx, {
        type: 'line',
        data: {
            labels: receivablesLabels,
            datasets: [{
                label: 'Receivables',
                data: receivablesData,
                borderColor: 'rgb(255, 159, 67)',
                backgroundColor: 'rgba(255, 159, 67, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: tickColor } },
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, callback: function(v) { return '₹' + v.toLocaleString(); } } }
            }
        }
    });

    // Payables Chart
    const payablesCtx = document.getElementById('payablesChart').getContext('2d');
    const payablesLabels = @json($dashboardPayablesLabels);
    const payablesData = @json($dashboardPayablesData);

    payablesChart = new Chart(payablesCtx, {
        type: 'line',
        data: {
            labels: payablesLabels,
            datasets: [{
                label: 'Payables',
                data: payablesData,
                borderColor: 'rgb(234, 84, 85)',
                backgroundColor: 'rgba(234, 84, 85, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { color: gridColor }, ticks: { color: tickColor } },
                y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, callback: function(v) { return '₹' + v.toLocaleString(); } } }
            }
        }
    });

    function buildRecentTransactions(transactions) {
        if (!transactions || !transactions.length) {
            return `<div class="list-group-item" style="padding: 24px; text-align: center; border: none;">
                        <div style="color: var(--text-muted); margin-bottom: 12px;">
                            <i class="bi bi-inbox" style="font-size: 36px;"></i>
                        </div>
                        <h6 style="color: var(--text-heading); margin-bottom: 4px;">No transactions yet</h6>
                        <p style="color: var(--text-muted); font-size: 12px; margin: 0;">Start by creating a voucher to see transactions here.</p>
                    </div>`;
        }

        return transactions.map(function(transaction) {
            const partyName = transaction.party?.name || 'No party';
            const voucherType = transaction.voucher_type ? transaction.voucher_type.charAt(0).toUpperCase() + transaction.voucher_type.slice(1) : 'Voucher';
            const voucherDate = formatDateIst(transaction.voucher_date);
            const amount = Number(transaction.total_debit || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            return `<a href="/admin/vouchers/${transaction.id}" class="list-group-item list-group-item-action px-3 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold">${transaction.voucher_number || 'Voucher #' + transaction.id}</div>
                                <div class="text-muted small">${partyName} · ${voucherType}</div>
                            </div>
                            <div class="text-end small text-muted">
                                <div>${voucherDate}</div>
                                <div>₹${amount}</div>
                            </div>
                        </div>
                    </a>`;
        }).join('');
    }

    function refreshDashboardCharts(data) {
        if (incomeExpenseChart) {
            incomeExpenseChart.data.labels = data.chart_data.labels;
            incomeExpenseChart.data.datasets[0].data = data.chart_data.income;
            incomeExpenseChart.data.datasets[1].data = data.chart_data.expense;
            incomeExpenseChart.update();
        }

        if (receivablesChart) {
            receivablesChart.data.labels = data.receivables_trend.labels;
            receivablesChart.data.datasets[0].data = data.receivables_trend.data;
            receivablesChart.update();
        }

        if (payablesChart) {
            payablesChart.data.labels = data.payables_trend.labels;
            payablesChart.data.datasets[0].data = data.payables_trend.data;
            payablesChart.update();
        }
    }

    function formatDashboardAmount(value) {
        return Number(value || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function refreshDashboardStats(statistics) {
        const periodLabel = (statistics.period && statistics.period.label) ? statistics.period.label : '';

        $('#statIncome').text('₹' + formatDashboardAmount(statistics.income));
        $('#statIncomeDetail').html('<i class="bi bi-calendar-check"></i> <span class="text-muted">' + periodLabel + '</span>');

        $('#statExpense').text('₹' + formatDashboardAmount(statistics.expense));
        $('#statExpenseDetail').html('<i class="bi bi-calendar-check"></i> <span class="text-muted">' + periodLabel + '</span>');

        const profit = Number(statistics.profit || 0);
        const isNetLoss = profit < 0;
        $('#statProfitLabel').text(isNetLoss ? 'Net Loss' : 'Net Profit');
        $('#statProfit')
            .text((isNetLoss ? '-' : '') + '₹' + formatDashboardAmount(Math.abs(profit)))
            .css('color', isNetLoss ? 'var(--danger)' : 'var(--success)');
        $('#statProfitDetail').html('<i class="bi bi-graph-up"></i> <span class="text-muted">' + periodLabel + '</span>');

        $('#statCashBalance').text('₹' + formatDashboardAmount(statistics.cash_balance));
        $('#statCashBalanceDetail').html('<i class="bi bi-wallet2"></i> Cash + Bank balance');

        $('#statReceivables').text('₹' + formatDashboardAmount(statistics.receivables));
        $('#statPayables').text('₹' + formatDashboardAmount(statistics.payables));
    }

    function refreshRecentTransactions(transactions) {
        $('#recentTransactionsList').html(buildRecentTransactions(transactions));
    }

    function showDashboardLoader() {
        $('#dashboardLoader').removeClass('d-none');
    }

    function hideDashboardLoader() {
        $('#dashboardLoader').addClass('d-none');
    }

    function fetchDashboardData(range, group) {
        const params = { range: range, group: group };
        showDashboardLoader();

        $.ajax({
            url: '{{ route('admin.dashboard') }}',
            method: 'GET',
            data: params,
            dataType: 'json',
            success: function(response) {
                refreshDashboardStats(response.statistics || {});
                refreshDashboardCharts(response);
                refreshRecentTransactions(response.recent_transactions || []);
            },
            error: function() {
                console.error('Unable to load dashboard data.');
            },
            complete: function() {
                hideDashboardLoader();
            }
        });
    }

    $('#dashboardRange').on('change', function() {
        const selectedRange = $(this).val();
        const activeGroup = $('#dashboardGroupButtons button.active').data('group') || '{{ $group ?? 'monthly' }}';
        fetchDashboardData(selectedRange, activeGroup);
    });

    $('#dashboardGroupButtons button').on('click', function() {
        const groupValue = $(this).data('group');
        $('#dashboardGroupButtons button').removeClass('active');
        $(this).addClass('active');
        const selectedRange = $('#dashboardRange').val() || '{{ $range ?? 'this_year' }}';
        fetchDashboardData(selectedRange, groupValue);
    });
});
</script>
@endpush
