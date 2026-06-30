@extends('layouts.app')

@section('title', 'Profit & Loss')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-graph-up-arrow"></i> Performance Snapshot</span>
                <h1 class="report-title">Profit & Loss Statement</h1>
                <p class="report-subtitle">Track income, expenses, and final profitability with a cleaner statement layout built for fast management review.</p>
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
        <form method="GET" action="{{ route('admin.reports.profit-loss') }}" class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-7">
                <label class="form-label">Financial Year</label>
                <select name="financial_year_id" class="form-select">
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" {{ ($financialYearId ?? '') == $fy->id ? 'selected' : '' }}>
                            {{ $fy->name }} ({{ $fy->start_date }} to {{ $fy->end_date }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-3 col-md-5 report-filter-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(!empty($financialYearId))
                    <a href="{{ route('admin.export.excel', ['type' => 'profit-loss', 'financial_year_id' => $financialYearId]) }}" class="btn btn-outline-success report-btn-export">
                        <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                    </a>
                    <a href="{{ route('admin.export.profit-loss.pdf', ['financial_year_id' => $financialYearId]) }}" class="btn btn-outline-danger report-btn-export">
                        <i class="bi bi-file-earmark-pdf"></i>PDF
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

@if(!$report)
    <div class="report-panel">
        <div class="report-empty">
            <div class="report-empty-icon"><i class="bi bi-calendar-x"></i></div>
            <div>
                <h5 class="mb-2">No financial year found</h5>
                <p class="mb-0">Create a financial year first to view profit and loss.</p>
            </div>
        </div>
    </div>
@else
    <div class="report-stats-grid">
        <div class="report-stat report-stat--success">
            <p class="report-stat-label">Total Income</p>
            <h3 class="report-stat-value">₹{{ number_format($report['income']['total'], 2) }}</h3>
            <p class="report-stat-note">Revenue and income recorded in the selected financial year.</p>
        </div>
        <div class="report-stat report-stat--danger">
            <p class="report-stat-label">Total Expenses</p>
            <h3 class="report-stat-value">₹{{ number_format($report['expense']['total'], 2) }}</h3>
            <p class="report-stat-note">All expense heads included in this period.</p>
        </div>
        <div class="report-stat {{ $report['is_profit'] ? 'report-stat--primary' : 'report-stat--warning' }}">
            <p class="report-stat-label">Net Result</p>
            <h3 class="report-stat-value">{{ $report['is_profit'] ? '+' : '-' }}₹{{ number_format(abs($report['net_profit']), 2) }}</h3>
            <p class="report-stat-note">{{ $report['is_profit'] ? 'Profit recorded for this period.' : 'Loss recorded for this period.' }}</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <div class="report-panel h-100">
                <div class="report-panel-header">
                    <h6 class="report-panel-title"><i class="bi bi-arrow-down-circle text-success"></i>Income</h6>
                    <span class="report-pill report-pill--success">₹{{ number_format($report['income']['total'], 2) }}</span>
                </div>
                <div class="report-panel-body report-panel-body--flush">
                    <table class="table report-table table-hover mb-0">
                        <tbody>
                            @forelse($report['income']['accounts'] as $item)
                            <tr>
                                <td>{{ $item['account']->account_name }}</td>
                                <td class="text-end fw-semibold text-success">₹{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td class="text-muted text-center py-3">No income recorded</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Income</td>
                                <td class="text-end fw-bold text-success">₹{{ number_format($report['income']['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="report-panel h-100">
                <div class="report-panel-header">
                    <h6 class="report-panel-title"><i class="bi bi-arrow-up-circle text-danger"></i>Expenses</h6>
                    <span class="report-pill report-pill--danger">₹{{ number_format($report['expense']['total'], 2) }}</span>
                </div>
                <div class="report-panel-body report-panel-body--flush">
                    <table class="table report-table table-hover mb-0">
                        <tbody>
                            @forelse($report['expense']['accounts'] as $item)
                            <tr>
                                <td>{{ $item['account']->account_name }}</td>
                                <td class="text-end fw-semibold text-danger">₹{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td class="text-muted text-center py-3">No expenses recorded</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Expenses</td>
                                <td class="text-end fw-bold text-danger">₹{{ number_format($report['expense']['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-body">
            <div class="report-kpi-bar">
                <span class="report-kpi-chip"><i class="bi bi-calculator"></i>Net {{ $report['is_profit'] ? 'Profit' : 'Loss' }}: {{ $report['is_profit'] ? '+' : '-' }}₹{{ number_format(abs($report['net_profit']), 2) }}</span>
                <span class="report-pill {{ $report['is_profit'] ? 'report-pill--success' : 'report-pill--danger' }}">
                    <i class="bi {{ $report['is_profit'] ? 'bi-check-circle' : 'bi-exclamation-circle' }}"></i>
                    {{ $report['is_profit'] ? 'Profitable' : 'Loss Position' }}
                </span>
            </div>
        </div>
    </div>
@endif
@endsection
