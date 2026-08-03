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
                <p class="report-subtitle">
                    Basis for Balance Sheet and Profit &amp; Loss — closing debit/credit balances for each ledger.
                </p>
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
                    <a href="{{ route('admin.export.excel', ['type' => 'trial-balance', 'financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-outline-success report-btn-export">
                        <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                    </a>
                    <a href="{{ route('admin.export.trial-balance.pdf', ['financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-outline-danger report-btn-export">
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
                <p class="report-stat-label">Closing Debit</p>
                <h3 class="report-stat-value">₹{{ number_format($report['total_debit'], 2) }}</h3>
                <p class="report-stat-note">Must equal closing credit when books tally.</p>
            </div>
            <div class="report-stat report-stat--warning">
                <p class="report-stat-label">Closing Credit</p>
                <h3 class="report-stat-value">₹{{ number_format($report['total_credit'], 2) }}</h3>
                <p class="report-stat-note">Closing balances across all ledgers.</p>
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
                <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
                <span class="report-pill {{ $report['is_balanced'] ? 'report-pill--success' : 'report-pill--danger' }}">
                    <i class="bi {{ $report['is_balanced'] ? 'bi-check-circle' : 'bi-exclamation-circle' }}"></i>
                    {{ $report['is_balanced'] ? 'Balanced' : 'Review Difference' }}
                </span>
            </div>
            <div class="report-panel-body report-panel-body--flush">
                <div class="report-table-tools">
                    <form method="GET" action="{{ route('admin.reports.trial-balance') }}" class="report-rows-form">
                        <input type="hidden" name="financial_year_id" value="{{ $financialYearId ?? '' }}">
                        <input type="hidden" name="date_from" value="{{ $dateFrom ?? '' }}">
                        <input type="hidden" name="date_to" value="{{ $dateTo ?? '' }}">
                        <label for="trial-balance-per-page" class="report-rows-label">Rows Per Page</label>
                        <select id="trial-balance-per-page" name="per_page" class="form-select form-select-sm report-rows-select" onchange="this.form.submit()">
                            @foreach([10, 25, 30, 50, 100] as $size)
                                <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table report-table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Account Code</th>
                                <th>Particulars</th>
                                <th>Type</th>
                                <th class="text-end">Debit (₹)</th>
                                <th class="text-end">Credit (₹)</th>
                                <th class="text-end">Balance (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accounts as $item)
                            <tr>
                                <td class="fw-semibold">{{ $item['account']->account_code }}</td>
                                <td>
                                    <a href="{{ route('admin.reports.ledger', ['account_id' => $item['account']->id]) }}" class="report-detail-link" title="View ledger">
                                        {{ $item['account']->account_name }}
                                    </a>
                                </td>
                                <td><span class="report-pill report-pill--info">{{ ucfirst($item['account']->account_type) }}</span></td>
                                <td class="text-end fw-semibold text-primary">{{ ($item['debit'] ?? 0) > 0 ? '₹' . number_format($item['debit'], 2) : '-' }}</td>
                                <td class="text-end fw-semibold text-danger">{{ ($item['credit'] ?? 0) > 0 ? '₹' . number_format($item['credit'], 2) : '-' }}</td>
                                <td class="text-end fw-bold">
                                    @if(($item['debit'] ?? 0) > 0)
                                        ₹{{ number_format($item['debit'], 2) }} DR
                                    @elseif(($item['credit'] ?? 0) > 0)
                                        ₹{{ number_format($item['credit'], 2) }} CR
                                    @else
                                        ₹0.00
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-muted text-center py-3">No accounts found</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">Total</td>
                                <td class="text-end fw-bold">₹{{ number_format($report['total_debit'], 2) }}</td>
                                <td class="text-end fw-bold">₹{{ number_format($report['total_credit'], 2) }}</td>
                                <td class="text-end fw-bold">
                                    {{ $report['is_balanced'] ? 'Balanced' : '₹' . number_format(abs($report['total_debit'] - $report['total_credit']), 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @if($accounts->hasPages())
                    <div class="report-pagination">
                        {{ $accounts->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
