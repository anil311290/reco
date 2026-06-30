@extends('layouts.app')

@section('title', 'Day Book')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-calendar-day"></i> Daily Activity</span>
                <h1 class="report-title">Day Book</h1>
                <p class="report-subtitle">Scan voucher activity for any selected date with quick totals and a cleaner transaction layout.</p>
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
            <p class="report-stat-label">Voucher Count</p>
            <h3 class="report-stat-value">{{ $report['vouchers']->count() }}</h3>
            <p class="report-stat-note">Transactions recorded for the selected day.</p>
        </div>
        <div class="report-stat report-stat--success">
            <p class="report-stat-label">Daily Total</p>
            <h3 class="report-stat-value">₹{{ number_format($report['total_debit'], 2) }}</h3>
            <p class="report-stat-note">Debit and credit totals for the day.</p>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-clock-history text-primary"></i>Transactions</h6>
            <span class="report-pill report-pill--info">{{ $report['vouchers']->count() }} vouchers</span>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Voucher #</th>
                        <th>Type</th>
                        <th>Party</th>
                        <th>Narration</th>
                        <th class="text-end">Debit (₹)</th>
                        <th class="text-end">Credit (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['vouchers'] as $voucher)
                    <tr>
                        <td class="fw-semibold">{{ $voucher->voucher_number }}</td>
                        <td><span class="report-pill report-pill--info">{{ ucfirst($voucher->voucher_type) }}</span></td>
                        <td>{{ $voucher->party->name ?? '-' }}</td>
                        <td class="text-muted small">{{ Str::limit($voucher->narration, 50) }}</td>
                        <td class="text-end fw-semibold">{{ $voucher->total_debit > 0 ? '₹' . number_format($voucher->total_debit, 2) : '-' }}</td>
                        <td class="text-end fw-semibold">{{ $voucher->total_credit > 0 ? '₹' . number_format($voucher->total_credit, 2) : '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center py-4">No transactions found for {{ $date }}</td></tr>
                    @endforelse
                </tbody>
                @if($report['vouchers']->count() > 0)
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
