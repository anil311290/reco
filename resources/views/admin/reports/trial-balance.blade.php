@extends('layouts.app')

@section('title', 'Trial Balance')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-journal-check"></i> Closing Control</span>
                <h1 class="report-title">Trial Balance</h1>
                <p class="report-subtitle">Validate the reporting period with a sharper debit-credit view and faster visibility into balance differences.</p>
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
        <form method="GET" action="{{ route('admin.reports.trial-balance') }}" class="row g-3 align-items-end">
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
            <div class="col-lg-3 col-md-5 report-filter-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(!empty($financialYearId))
                    <a href="{{ route('admin.export.excel', ['type' => 'trial-balance', 'financial_year_id' => $financialYearId]) }}" class="btn btn-outline-success report-btn-export">
                        <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                    </a>
                    <a href="{{ route('admin.export.trial-balance.pdf', ['financial_year_id' => $financialYearId]) }}" class="btn btn-outline-danger report-btn-export">
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
                    <p class="mb-0">Create a financial year first to generate the trial balance.</p>
                </div>
            </div>
        </div>
    @else
        <div class="report-stats-grid">
            <div class="report-stat report-stat--info">
                <p class="report-stat-label">Total Debit</p>
                <h3 class="report-stat-value">₹{{ number_format($report['total_debit'], 2) }}</h3>
                <p class="report-stat-note">Debit-side total across all participating accounts.</p>
            </div>
            <div class="report-stat report-stat--warning">
                <p class="report-stat-label">Total Credit</p>
                <h3 class="report-stat-value">₹{{ number_format($report['total_credit'], 2) }}</h3>
                <p class="report-stat-note">Credit-side total across all participating accounts.</p>
            </div>
            <div class="report-stat {{ $report['is_balanced'] ? 'report-stat--success' : 'report-stat--danger' }}">
                <p class="report-stat-label">Status</p>
                <h3 class="report-stat-value">{{ $report['is_balanced'] ? 'Balanced' : 'Mismatch' }}</h3>
                <p class="report-stat-note">{{ $report['is_balanced'] ? 'Trial balance is closed cleanly.' : 'Difference: ₹' . number_format(abs($report['total_debit'] - $report['total_credit']), 2) }}</p>
            </div>
        </div>

        <div class="report-panel">
            <div class="report-panel-header">
                <h6 class="report-panel-title"><i class="bi bi-list-check text-primary"></i>Account Balances</h6>
                <span class="report-pill {{ $report['is_balanced'] ? 'report-pill--success' : 'report-pill--danger' }}">
                    <i class="bi {{ $report['is_balanced'] ? 'bi-check-circle' : 'bi-exclamation-circle' }}"></i>
                    {{ $report['is_balanced'] ? 'Balanced' : 'Review Difference' }}
                </span>
            </div>
            <div class="report-panel-body report-panel-body--flush">
                <table class="table report-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Account Code</th>
                            <th>Account Name</th>
                            <th>Type</th>
                            <th class="text-end">Debit (₹)</th>
                            <th class="text-end">Credit (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($report['accounts'] as $item)
                        <tr>
                            <td>{{ $item['account']->account_code }}</td>
                            <td>{{ $item['account']->account_name }}</td>
                            <td><span class="report-pill report-pill--info">{{ ucfirst($item['account']->account_type) }}</span></td>
                            <td class="text-end fw-semibold">{{ $item['debit'] > 0 ? '₹' . number_format($item['debit'], 2) : '-' }}</td>
                            <td class="text-end fw-semibold">{{ $item['credit'] > 0 ? '₹' . number_format($item['credit'], 2) : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-muted text-center py-3">No accounts found</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Total</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['total_debit'], 2) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['total_credit'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
