@extends('layouts.app')

@section('title', 'Day Book')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-calendar-day"></i> Books of Accounts</span>
                <h1 class="report-title">Day Book</h1>
                <p class="report-subtitle">All posted voucher lines for the selected date — Tally-style daily book with particulars.</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="{{ route('admin.reports.day-book') }}" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}">
            </div>
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
            <div class="col-lg-auto col-md-12 report-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('admin.export.excel', ['type' => 'day-book', 'date' => $date, 'financial_year_id' => $financialYearId ?? '']) }}" class="btn btn-outline-success report-btn-export">
                    <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                </a>
                <a href="{{ route('admin.export.day-book.pdf', ['date' => $date, 'financial_year_id' => $financialYearId ?? '']) }}" class="btn btn-outline-danger report-btn-export">
                    <i class="bi bi-file-earmark-pdf"></i>PDF
                </a>
            </div>
        </form>
    </div>

    <div class="report-stats-grid">
        <div class="report-stat report-stat--info">
            <p class="report-stat-label">Report Date</p>
            <h3 class="report-stat-value">{{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
            <p class="report-stat-note">Selected day for voucher activity.</p>
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
            <span class="report-pill report-pill--info">{{ count($report['rows']) }} lines</span>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
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
                    @forelse($report['rows'] as $row)
                    <tr>
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
                    <tr><td colspan="7" class="text-muted text-center py-4">No posted transactions found for {{ $date }}</td></tr>
                    @endforelse
                </tbody>
                @if(count($report['rows']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="5">Total</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['total_debit'], 2) }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['total_credit'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
