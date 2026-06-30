@extends('layouts.app')

@section('title', 'Reports')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-bar-chart-line"></i> Reporting Hub</span>
                <h1 class="report-title">Financial Reports</h1>
                <p class="report-subtitle">A cleaner analytics workspace for statutory statements, ledgers, daily activity, and aging views. Open any report below to review the latest financial position.</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <span class="report-pill report-pill--info"><i class="bi bi-grid-1x2"></i> 8 report views available</span>
                </div>
            </div>
        </div>
    </div>

    <div class="report-feature-grid">
        <div class="report-feature-card" style="--report-icon-start:#16a34a; --report-icon-end:#22c55e;"><div class="report-feature-icon"><i class="bi bi-graph-up-arrow"></i></div><h5 class="report-feature-title">Profit & Loss</h5><p class="report-feature-text">Review income, expenses, and profitability with clearer summaries and statement sections.</p><a href="{{ route('admin.reports.profit-loss') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open Report</a></div>
        <div class="report-feature-card" style="--report-icon-start:#2563eb; --report-icon-end:#60a5fa;"><div class="report-feature-icon"><i class="bi bi-file-earmark-bar-graph"></i></div><h5 class="report-feature-title">Balance Sheet</h5><p class="report-feature-text">Inspect assets, liabilities, equity, and balance status in a more readable financial position view.</p><a href="{{ route('admin.reports.balance-sheet') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open Report</a></div>
        <div class="report-feature-card" style="--report-icon-start:#d97706; --report-icon-end:#fbbf24;"><div class="report-feature-icon"><i class="bi bi-journal-check"></i></div><h5 class="report-feature-title">Trial Balance</h5><p class="report-feature-text">Verify debit and credit parity quickly with clearer totals and difference visibility.</p><a href="{{ route('admin.reports.trial-balance') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open Report</a></div>
        <div class="report-feature-card" style="--report-icon-start:#0891b2; --report-icon-end:#38bdf8;"><div class="report-feature-icon"><i class="bi bi-calendar-day"></i></div><h5 class="report-feature-title">Day Book</h5><p class="report-feature-text">Scan daily voucher activity with totals, voucher mix, and compact transaction review.</p><a href="{{ route('admin.reports.day-book') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open Report</a></div>
        <div class="report-feature-card" style="--report-icon-start:#475569; --report-icon-end:#94a3b8;"><div class="report-feature-icon"><i class="bi bi-book"></i></div><h5 class="report-feature-title">Ledger</h5><p class="report-feature-text">View running balances, opening positions, and detailed account movement with period filters.</p><a href="{{ route('admin.reports.ledger') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open Report</a></div>
        <div class="report-feature-card" style="--report-icon-start:#059669; --report-icon-end:#34d399;"><div class="report-feature-icon"><i class="bi bi-currency-exchange"></i></div><h5 class="report-feature-title">Cash Flow</h5><p class="report-feature-text">Track inflows, outflows, and net liquidity movement with quick export actions.</p><a href="{{ route('admin.reports.cash-flow') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open Report</a></div>
        <div class="report-feature-card" style="--report-icon-start:#dc2626; --report-icon-end:#fb7185;"><div class="report-feature-icon"><i class="bi bi-people"></i></div><h5 class="report-feature-title">AR Aging</h5><p class="report-feature-text">Review receivables exposure and debtor-level outstanding balances in one place.</p><a href="{{ route('admin.reports.debtors-outstanding') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open Report</a></div>
        <div class="report-feature-card" style="--report-icon-start:#7c3aed; --report-icon-end:#c084fc;"><div class="report-feature-icon"><i class="bi bi-people-fill"></i></div><h5 class="report-feature-title">AP Aging</h5><p class="report-feature-text">Monitor payables exposure and creditor-level balances with cleaner summary tiles.</p><a href="{{ route('admin.reports.creditors-outstanding') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open Report</a></div>
    </div>
</div>
@endsection
