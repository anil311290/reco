@extends('layouts.app')

@section('title', 'Cash Flow')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-currency-exchange"></i> Liquidity Snapshot</span>
                <h1 class="report-title">Cash Flow Statement</h1>
                <p class="report-subtitle">Monitor inflows, outflows, and net liquidity movement with a cleaner dashboard-style summary for each financial year.</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft">
                        <i class="bi bi-arrow-left me-1"></i>Back to Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="{{ route('admin.reports.cash-flow') }}" class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Financial Year</label>
                <select name="financial_year_id" class="form-select">
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" {{ ($financialYearId ?? '') == $fy->id ? 'selected' : '' }}>
                            {{ $fy->name }} ({{ $fy->start_date }} to {{ $fy->end_date }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-8 col-md-6 report-filter-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(!empty($financialYearId))
                    <a href="{{ route('admin.export.excel', ['type' => 'cash-flow', 'financial_year_id' => $financialYearId]) }}" class="btn btn-outline-success report-btn-export">
                        <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                    </a>
                    <a href="{{ route('admin.export.cash-flow.pdf', ['financial_year_id' => $financialYearId]) }}" class="btn btn-outline-danger report-btn-export">
                        <i class="bi bi-file-earmark-pdf"></i>PDF
                    </a>
                @endif
            </div>
        </form>
    </div>

    @if(!$report)
        <div class="report-panel">
            <div class="report-empty">
                <div class="report-empty-icon"><i class="bi bi-calendar-x"></i></div>
                <div>
                    <h5 class="mb-2">No financial year found</h5>
                    <p class="mb-0">Create a financial year first to generate the cash flow statement.</p>
                </div>
            </div>
        </div>
    @else
        <div class="report-stats-grid">
            <div class="report-stat report-stat--success">
                <p class="report-stat-label">Cash Inflows</p>
                <h3 class="report-stat-value">₹{{ number_format($report['inflows'], 2) }}</h3>
                <p class="report-stat-note">All debit-side movement into cash and bank accounts.</p>
            </div>
            <div class="report-stat report-stat--danger">
                <p class="report-stat-label">Cash Outflows</p>
                <h3 class="report-stat-value">₹{{ number_format($report['outflows'], 2) }}</h3>
                <p class="report-stat-note">All credit-side movement out of cash and bank accounts.</p>
            </div>
            <div class="report-stat {{ $report['net_cash_flow'] >= 0 ? 'report-stat--primary' : 'report-stat--warning' }}">
                <p class="report-stat-label">Net Cash Flow</p>
                <h3 class="report-stat-value">₹{{ number_format($report['net_cash_flow'], 2) }}</h3>
                <p class="report-stat-note">{{ $report['net_cash_flow'] >= 0 ? 'Positive movement in liquidity.' : 'Negative movement in liquidity.' }}</p>
            </div>
        </div>

        <div class="report-panel">
            <div class="report-panel-body">
                <div class="report-kpi-bar">
                    <span class="report-kpi-chip"><i class="bi bi-arrow-down-left-circle"></i>Inflows: ₹{{ number_format($report['inflows'], 2) }}</span>
                    <span class="report-kpi-chip"><i class="bi bi-arrow-up-right-circle"></i>Outflows: ₹{{ number_format($report['outflows'], 2) }}</span>
                    <span class="report-pill {{ $report['net_cash_flow'] >= 0 ? 'report-pill--success' : 'report-pill--danger' }}">{{ $report['net_cash_flow'] >= 0 ? 'Positive Cash Position' : 'Negative Cash Position' }}</span>
                </div>
                <p class="text-muted mb-0 mt-3">This statement summarizes movement through cash and bank accounts for the selected financial year. Inflows are debit entries; outflows are credit entries.</p>
            </div>
        </div>
    @endif
</div>
@endsection
