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
            <div class="col-lg-4 col-md-7">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="{{ $date }}">
            </div>
            <div class="col-lg-3 col-md-5 report-filter-actions">
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                <a href="{{ route('admin.export.excel', ['type' => 'day-book', 'date' => $date]) }}" class="btn btn-outline-success report-btn-export">
                    <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                </a>
                <a href="{{ route('admin.export.day-book.pdf', ['date' => $date]) }}" class="btn btn-outline-danger report-btn-export">
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
                        <th>Narration</th>
                        <th class="text-end">Debit (₹)</th>
                        <th class="text-end">Credit (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['rows'] as $row)
                    <tr>
                        <td class="fw-semibold">{{ $row['voucher_number'] }}</td>
                        <td><span class="report-pill report-pill--info">{{ ucfirst($row['voucher_type']) }}</span></td>
                        <td>{{ $row['account_name'] }}</td>
                        <td class="text-muted small">{{ Str::limit($row['narration'] ?? ($row['party_name'] ?? '-'), 60) }}</td>
                        <td class="text-end fw-semibold">{{ $row['debit'] > 0 ? '₹' . number_format($row['debit'], 2) : '-' }}</td>
                        <td class="text-end fw-semibold">{{ $row['credit'] > 0 ? '₹' . number_format($row['credit'], 2) : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center py-4">No posted transactions found for {{ $date }}</td></tr>
                    @endforelse
                </tbody>
                @if(count($report['rows']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="4">Total</td>
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
