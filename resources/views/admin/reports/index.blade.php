@extends('layouts.app')

@section('title', 'Reports')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-bar-chart-line"></i> Books &amp; Statements</span>
                <h1 class="report-title">Financial Reports</h1>
                <p class="report-subtitle">Tally-style sequence: books first, then trial balance and statutory statements, then receivables / payables.</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <span class="report-pill report-pill--info"><i class="bi bi-grid-1x2"></i> 8 report views</span>
                </div>
            </div>
        </div>
    </div>

    <div class="report-feature-grid">
        <div class="report-feature-card" style="--report-icon-start:#0891b2; --report-icon-end:#38bdf8;">
            <div class="report-feature-icon"><i class="bi bi-calendar-day"></i></div>
            <h5 class="report-feature-title">Day Book</h5>
            <p class="report-feature-text">All posted voucher lines for a selected date with debit / credit particulars.</p>
            <a href="{{ route('admin.reports.day-book') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open</a>
        </div>
        <div class="report-feature-card" style="--report-icon-start:#475569; --report-icon-end:#94a3b8;">
            <div class="report-feature-icon"><i class="bi bi-book"></i></div>
            <h5 class="report-feature-title">Ledger</h5>
            <p class="report-feature-text">Account-wise ledger with opening balance, running balance, and period filter.</p>
            <a href="{{ route('admin.reports.ledger') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open</a>
        </div>
        <div class="report-feature-card" style="--report-icon-start:#d97706; --report-icon-end:#fbbf24;">
            <div class="report-feature-icon"><i class="bi bi-journal-check"></i></div>
            <h5 class="report-feature-title">Trial Balance</h5>
            <p class="report-feature-text">Debit and credit closing balances for all ledgers — books must tally.</p>
            <a href="{{ route('admin.reports.trial-balance') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open</a>
        </div>
        <div class="report-feature-card" style="--report-icon-start:#16a34a; --report-icon-end:#22c55e;">
            <div class="report-feature-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <h5 class="report-feature-title">Profit &amp; Loss</h5>
            <p class="report-feature-text">Income and expense summary with net profit / loss for the financial year.</p>
            <a href="{{ route('admin.reports.profit-loss') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open</a>
        </div>
        <div class="report-feature-card" style="--report-icon-start:#059669; --report-icon-end:#34d399;">
            <div class="report-feature-icon"><i class="bi bi-cash-coin"></i></div>
            <h5 class="report-feature-title">Receipt &amp; Payment</h5>
            <p class="report-feature-text">Cash, bank, and OD movement head-wise with opening and closing balances.</p>
            <a href="{{ route('admin.reports.receipt-payment') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open</a>
        </div>
        <div class="report-feature-card" style="--report-icon-start:#2563eb; --report-icon-end:#60a5fa;">
            <div class="report-feature-icon"><i class="bi bi-file-earmark-bar-graph"></i></div>
            <h5 class="report-feature-title">Balance Sheet</h5>
            <p class="report-feature-text">Assets, liabilities, and equity — financial position as on date.</p>
            <a href="{{ route('admin.reports.balance-sheet') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open</a>
        </div>
        <div class="report-feature-card" style="--report-icon-start:#dc2626; --report-icon-end:#fb7185;">
            <div class="report-feature-icon"><i class="bi bi-people"></i></div>
            <h5 class="report-feature-title">Receivables</h5>
            <p class="report-feature-text">Debtors outstanding from party ledger balances (not fake aging).</p>
            <a href="{{ route('admin.reports.debtors-outstanding') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open</a>
        </div>
        <div class="report-feature-card" style="--report-icon-start:#7c3aed; --report-icon-end:#c084fc;">
            <div class="report-feature-icon"><i class="bi bi-people-fill"></i></div>
            <h5 class="report-feature-title">Payables</h5>
            <p class="report-feature-text">Creditors outstanding from party ledger balances.</p>
            <a href="{{ route('admin.reports.creditors-outstanding') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open</a>
        </div>
        <div class="report-feature-card" style="--report-icon-start:#0d9488; --report-icon-end:#5eead4;">
            <div class="report-feature-icon"><i class="bi bi-link-45deg"></i></div>
            <h5 class="report-feature-title">Settlement Audit</h5>
            <p class="report-feature-text">Payment-to-invoice mapping trail — which receipt/payment settled which invoice.</p>
            <a href="{{ route('admin.reports.settlement-audit') }}" class="btn btn-outline-primary"><i class="bi bi-eye me-2"></i>Open</a>
        </div>
    </div>
</div>
@endsection
