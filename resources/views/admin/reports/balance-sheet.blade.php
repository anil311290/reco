@extends('layouts.app')

@section('title', 'Balance Sheet')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-file-earmark-bar-graph"></i> Financial Position</span>
                <h1 class="report-title">Balance Sheet</h1>
                <p class="report-subtitle">Review assets, liabilities, and equity in a cleaner statement layout with faster year switching and a clearer balance check.</p>
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
        <div class="report-filter-head">
            <span class="report-filter-head-title"><i class="bi bi-funnel"></i> Filters</span>
            <a href="{{ route('admin.reports.balance-sheet') }}" class="report-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            <button type="button" class="report-filter-toggle" aria-expanded="false" aria-label="Toggle filters"><i class="bi bi-chevron-down"></i></button>
        </div>
        <form method="GET" action="{{ route('admin.reports.balance-sheet') }}" class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-7">
                <label class="form-label">Financial Year</label>
                <select name="financial_year_id" class="form-select">
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" {{ ($financialYearId ?? '') == $fy->id ? 'selected' : '' }}>
                            {{ $fy->name }} (@istDate($fy->start_date) to @istDate($fy->end_date))
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
            </div>
            <div class="col-lg-auto col-md-12 report-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(!empty($financialYearId))
                    <div class="btn-group report-export-dropdown">
                        <button type="button" class="btn report-btn-export-neutral dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i>Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('admin.export.excel', ['type' => 'balance-sheet', 'financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Excel</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.export.balance-sheet.pdf', ['financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF</a></li>
                        </ul>
                    </div>
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
                <p class="mb-0">Create a financial year first to generate a balance sheet.</p>
            </div>
        </div>
    </div>
@else
    <div class="report-stats-grid">
        <div class="report-stat report-stat--primary">
            <p class="report-stat-label">Total Assets</p>
            <h3 class="report-stat-value">₹{{ number_format($report['assets']['total'], 2) }}</h3>
            <p class="report-stat-note">Current asset-side total for the selected year.</p>
        </div>
        <div class="report-stat report-stat--danger">
            <p class="report-stat-label">Liabilities + Equity</p>
            <h3 class="report-stat-value">₹{{ number_format($report['total_liabilities_equity'], 2) }}</h3>
            <p class="report-stat-note">Combined closing position on the source side.</p>
        </div>
        <div class="report-stat {{ $report['is_balanced'] ? 'report-stat--success' : 'report-stat--warning' }}">
            <p class="report-stat-label">Balance Status</p>
            <h3 class="report-stat-value">{{ $report['is_balanced'] ? 'Balanced' : 'Review Needed' }}</h3>
            <p class="report-stat-note">
                {{ $report['is_balanced'] ? 'Assets match liabilities and equity.' : 'Difference: ₹' . number_format(abs($report['assets']['total'] - $report['total_liabilities_equity']), 2) }}
            </p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <h5 class="report-section-title mb-3"><i class="bi bi-shield-exclamation text-danger me-1"></i>Liabilities</h5>
            <div class="report-panel mb-4">
                <div class="report-panel-header">
                    <h6 class="report-panel-title"><i class="bi bi-stars text-success"></i>Equity</h6>
                    <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
                </div>
                <div class="report-panel-body report-panel-body--flush">
                    <table class="table report-table table-hover mb-0">
                        <tbody>
                            @forelse($report['equity']['accounts'] as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.reports.ledger', ['account_id' => $item['account']->id]) }}" class="report-detail-link" title="View ledger">
                                        {{ $item['account']->account_name }}
                                    </a>
                                </td>
                                <td class="text-end fw-semibold">₹{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td class="text-muted text-center py-3">No equity accounts found</td></tr>
                            @endforelse
                            <tr>
                                <td>Net Profit/Loss</td>
                                <td class="text-end fw-semibold {{ $report['equity']['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ $report['equity']['net_profit'] >= 0 ? '+' : '' }}₹{{ number_format($report['equity']['net_profit'], 2) }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Equity</td>
                                <td class="text-end fw-bold">₹{{ number_format($report['equity']['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="report-panel">
                <div class="report-panel-header">
                    <h6 class="report-panel-title"><i class="bi bi-shield-exclamation text-danger"></i>Other Liabilities</h6>
                    <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
                </div>
                <div class="report-panel-body report-panel-body--flush">
                    <table class="table report-table table-hover mb-0">
                        <tbody>
                            @forelse($report['liabilities']['accounts'] as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.reports.ledger', ['account_id' => $item['account']->id]) }}" class="report-detail-link" title="View ledger">
                                        {{ $item['account']->account_name }}
                                    </a>
                                </td>
                                <td class="text-end fw-semibold">₹{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td class="text-muted text-center py-3">No liability accounts found</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Other Liabilities</td>
                                <td class="text-end fw-bold">₹{{ number_format($report['liabilities']['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="report-panel mt-4">
                <div class="report-panel-body">
                    <div class="report-kpi-bar">
                        <span class="report-kpi-chip"><i class="bi bi-calculator"></i>Total Liabilities + Equity: ₹{{ number_format($report['total_liabilities_equity'], 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <h5 class="report-section-title mb-3"><i class="bi bi-box-seam text-primary me-1"></i>Assets</h5>
            <div class="report-panel h-100">
                <div class="report-panel-header">
                    <h6 class="report-panel-title"><i class="bi bi-box-seam text-primary"></i>Assets</h6>
                    <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
                </div>
                <div class="report-panel-body report-panel-body--flush">
                    <table class="table report-table table-hover">
                        <tbody>
                            @forelse($report['assets']['accounts'] as $item)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.reports.ledger', ['account_id' => $item['account']->id]) }}" class="report-detail-link" title="View ledger">
                                        {{ $item['account']->account_name }}
                                    </a>
                                </td>
                                <td class="text-end fw-semibold">₹{{ number_format($item['amount'], 2) }}</td>
                            </tr>
                            @empty
                            <tr><td class="text-muted text-center py-3">No asset accounts found</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Assets</td>
                                <td class="text-end fw-bold">₹{{ number_format($report['assets']['total'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="report-panel mt-4">
                <div class="report-panel-body">
                    <div class="report-kpi-bar">
                        <span class="report-kpi-chip"><i class="bi bi-calculator"></i>Total Assets: ₹{{ number_format($report['assets']['total'], 2) }}</span>
                        <span class="report-pill {{ $report['is_balanced'] ? 'report-pill--success' : 'report-pill--danger' }}">
                            <i class="bi {{ $report['is_balanced'] ? 'bi-check-circle' : 'bi-x-circle' }}"></i>
                            {{ $report['is_balanced'] ? 'Balanced' : 'Not Balanced' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
</div>
@endsection
