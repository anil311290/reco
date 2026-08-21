@extends('layouts.app')

@section('title', 'Day Book')

@include('admin.reports._theme')

@push('styles')
<style>
    .day-book-page .report-stats-grid {
        gap: 0.75rem;
        grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
        margin: 8px 0;
    }

    .day-book-page .report-stat {
        padding: 0.85rem 0.95rem;
        min-height: 128px;
    }

    .day-book-page .report-stat-label {
        font-size: 0.72rem;
    }

    .day-book-page .report-stat-value {
        margin-top: 0.35rem;
        font-size: clamp(1.15rem, 1.9vw, 1.75rem);
        line-height: 1.25;
    }

    .day-book-page .report-stat-note {
        margin-top: 0.3rem;
        font-size: 0.81rem;
        line-height: 1.35;
    }

    .day-book-page .day-book-date-col {
        min-width: 112px;
        white-space: nowrap;
        word-break: normal;
        overflow-wrap: normal;
    }

    @media (max-width: 991.98px) {
        .day-book-page .report-stat {
            min-height: 112px;
        }
    }
</style>
@endpush

@section('content')
<div class="reports-shell day-book-page">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-calendar-day"></i> Books of Accounts</span>
                <h1 class="report-title">Day Book</h1>
                <p class="report-subtitle">All posted voucher lines for the selected date — Tally-style daily book with particulars.</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Accounting Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <div class="report-filter-head">
            <span class="report-filter-head-title"><i class="bi bi-funnel"></i> Filters</span>
            <a href="{{ route('admin.reports.day-book') }}" class="report-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            <button type="button" class="report-filter-toggle" aria-expanded="false" aria-label="Toggle filters"><i class="bi bi-chevron-down"></i></button>
        </div>
        <form method="GET" action="{{ route('admin.reports.day-book') }}" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Financial Year</label>
                <select name="financial_year_id" class="form-select">
                    @foreach($financialYears ?? [] as $fy)
                        <option value="{{ $fy->id }}" {{ (string) ($financialYearId ?? '') === (string) $fy->id ? 'selected' : '' }}>
                            {{ $fy->name }}{{ $fy->is_current ? ' (Current)' : '' }}{{ $fy->is_closed ? ' (Closed)' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-lg-2 col-md-6">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-lg-auto col-md-12 report-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <div class="btn-group report-export-dropdown">
                    <button type="button" class="btn report-btn-export-neutral dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i>Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.export.excel', ['type' => 'day-book', 'date_from' => $dateFrom, 'date_to' => $dateTo, 'date' => $dateFrom, 'financial_year_id' => $financialYearId ?? '']) }}"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Excel</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.export.day-book.pdf', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'financial_year_id' => $financialYearId ?? '']) }}"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>

    <div class="report-stats-grid">
        <div class="report-stat report-stat--info">
            <p class="report-stat-label">Report Period</p>
            <h3 class="report-stat-value">@istDate($dateFrom) to @istDate($dateTo)</h3>
            <p class="report-stat-note">Selected date range for voucher activity.</p>
        </div>
        <div class="report-stat report-stat--primary">
            <p class="report-stat-label">Vouchers</p>
            <h3 class="report-stat-value">{{ $report['vouchers']->count() }}</h3>
            <p class="report-stat-note">Posted vouchers for the day.</p>
        </div>
        <div class="report-stat report-stat--success">
            <p class="report-stat-label">Total Debit</p>
            <h3 class="report-stat-value">₹{{ number_format($report['total_debit'], 2) }}</h3>
            <p class="report-stat-note">Must equal Total Credit.</p>
        </div>
        <div class="report-stat report-stat--warning">
            <p class="report-stat-label">Total Credit</p>
            <h3 class="report-stat-value">₹{{ number_format($report['total_credit'], 2) }}</h3>
            <p class="report-stat-note">Must equal Total Debit.</p>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-clock-history text-primary"></i>Day Book Entries</h6>
            <span class="report-pill report-pill--info">{{ $rows->total() }} lines</span>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <div class="report-table-tools">
                <form method="GET" action="{{ route('admin.reports.day-book') }}" class="report-rows-form">
                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                    <input type="hidden" name="financial_year_id" value="{{ $financialYearId ?? '' }}">
                    <label for="day-book-per-page" class="report-rows-label">Rows Per Page</label>
                    <select id="day-book-per-page" name="per_page" class="form-select form-select-sm report-rows-select" onchange="this.form.submit()">
                        @foreach([10, 25, 40, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                        <option value="all" {{ strtolower((string) request('per_page', 10)) === 'all' ? 'selected' : '' }}>All</option>
                    </select>
                </form>
            </div>
            <div class="table-responsive">
                <table class="table report-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:56px">#</th>
                            <th class="day-book-date-col">Date</th>
                            <th>Voucher #</th>
                            <th>Type</th>
                            <th>Particulars</th>
                            <th>Party</th>
                            <th>Narration</th>
                            <th class="text-end">Debit (₹)</th>
                            <th class="text-end">Credit (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rows as $row)
                        <tr>
                            <td class="text-muted">{{ $row['serial'] ?? ($loop->iteration) }}</td>
                            <td class="day-book-date-col">@istDate($row['voucher_date'] ?? null)</td>
                            <td class="fw-semibold">
                                @if(!empty($row['voucher_id']))
                                    <a href="{{ route('admin.vouchers.show', $row['voucher_id']) }}" class="report-detail-link" title="View voucher">
                                        {{ $row['voucher_number'] }}
                                    </a>
                                @else
                                    {{ $row['voucher_number'] }}
                                @endif
                            </td>
                            <td><span class="report-pill report-pill--info">{{ ucfirst($row['voucher_type']) }}</span></td>
                            <td>
                                @if(!empty($row['account_id']))
                                    <a href="{{ route('admin.reports.ledger', ['account_id' => $row['account_id']]) }}" class="report-detail-link" title="View ledger">
                                        {{ $row['account_name'] }}
                                    </a>
                                @else
                                    {{ $row['account_name'] }}
                                @endif
                            </td>
                            <td>
                                @if(!empty($row['party_id']))
                                    <a href="{{ route('admin.parties.show', $row['party_id']) }}" class="report-detail-link" title="View party history">
                                        {{ $row['party_name'] }}
                                    </a>
                                @else
                                    {{ $row['party_name'] ?? '-' }}
                                @endif
                            </td>
                            <td class="text-muted small">
                                @if(!empty($row['sales_invoice_id']))
                                    <a href="{{ route('admin.sales-invoices.show', $row['sales_invoice_id']) }}" class="report-detail-link me-1" title="View sales invoice">Invoice</a>
                                @elseif(!empty($row['purchase_invoice_id']))
                                    <a href="{{ route('admin.purchase-invoices.show', $row['purchase_invoice_id']) }}" class="report-detail-link me-1" title="View purchase invoice">Invoice</a>
                                @endif
                                {{ Str::limit($row['narration'] ?? '-', 60) }}
                            </td>
                            <td class="text-end fw-semibold">{{ $row['debit'] > 0 ? '₹' . number_format($row['debit'], 2) : '-' }}</td>
                            <td class="text-end fw-semibold">{{ $row['credit'] > 0 ? '₹' . number_format($row['credit'], 2) : '-' }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="9" class="text-muted text-center py-4">No posted transactions found for the selected period.</td></tr>
                        @endforelse
                    </tbody>
                    @if($rows->count() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="7">Total</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['total_debit'], 2) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['total_credit'], 2) }}</td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
            @if($rows->hasPages())
                <div class="report-pagination">
                    {{ $rows->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
