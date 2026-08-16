@extends('layouts.app')

@section('title', 'Settlement Audit Report')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-link-45deg"></i> Payment Trail</span>
                <h1 class="report-title">Settlement Audit Report</h1>
                <p class="report-subtitle">Every payment/receipt mapped to the invoices it settles.</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <div class="report-filter-head">
            <span class="report-filter-head-title"><i class="bi bi-funnel"></i> Filters</span>
            <a href="{{ route('admin.reports.settlement-audit') }}" class="report-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            <button type="button" class="report-filter-toggle" aria-expanded="false" aria-label="Toggle filters"><i class="bi bi-chevron-down"></i></button>
        </div>
        <form method="GET" action="{{ route('admin.reports.settlement-audit') }}" class="row g-3 align-items-end">
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select" data-searchable="false">
                    @foreach(['all' => 'All', 'pending' => 'Pending', 'partial' => 'Partial', 'full' => 'Full', 'reversed' => 'Reversed'] as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['status'] ?? 'all') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">Invoice Type</label>
                <select name="type" class="form-select" data-searchable="false">
                    @foreach(['all' => 'All', 'sales' => 'Sales', 'purchase' => 'Purchase'] as $value => $label)
                        <option value="{{ $value }}" {{ ($filters['type'] ?? 'all') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-lg-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Apply</button>
            </div>
        </form>
    </div>

    <div class="row g-3">
        <div class="col-md-3">
            <div class="report-panel"><div class="report-panel-body">
                <div class="text-muted small">Total Mappings</div>
                <div class="fs-4 fw-bold">{{ number_format($report['summary']['total_mappings'] ?? 0) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="report-panel"><div class="report-panel-body">
                <div class="text-muted small">Total Allocated</div>
                <div class="fs-4 fw-bold">₹{{ number_format($report['summary']['total_allocated'] ?? 0, 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="report-panel"><div class="report-panel-body">
                <div class="text-muted small">Total Settled</div>
                <div class="fs-4 fw-bold text-success">₹{{ number_format($report['summary']['total_settled'] ?? 0, 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="report-panel"><div class="report-panel-body">
                <div class="text-muted small">Total Outstanding</div>
                <div class="fs-4 fw-bold text-danger">₹{{ number_format($report['summary']['total_outstanding'] ?? 0, 2) }}</div>
            </div></div>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-link-45deg text-primary"></i>Payment-Invoice Mappings</h6>
            <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Payment Voucher</th>
                        <th>Invoice</th>
                        <th>Party</th>
                        <th class="text-end">Allocated (₹)</th>
                        <th class="text-end">Settled (₹)</th>
                        <th class="text-end">Outstanding (₹)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mappings as $row)
                    <tr>
                        <td>{{ ($mappings->firstItem() ?? 1) + $loop->index }}</td>
                        <td>
                            {{ $row['payment_voucher_number'] }}
                            <div class="text-muted small">{{ !empty($row['payment_date']) ? \Carbon\Carbon::parse($row['payment_date'])->format('d/m/Y') : '-' }}</div>
                        </td>
                        <td>
                            {{ $row['invoice_number'] }}
                            <span class="badge bg-light text-dark border">{{ ucfirst($row['invoice_type']) }}</span>
                        </td>
                        <td>{{ $row['party_name'] }}</td>
                        <td class="text-end">₹{{ number_format((float) $row['amount_allocated'], 2) }}</td>
                        <td class="text-end text-success">₹{{ number_format((float) $row['amount_settled'], 2) }}</td>
                        <td class="text-end text-danger">₹{{ number_format((float) $row['outstanding'], 2) }}</td>
                        <td>
                            @php
                            $statusColors = ['pending' => 'secondary', 'partial' => 'warning', 'full' => 'success', 'reversed' => 'dark'];
                            @endphp
                            <span class="badge bg-{{ $statusColors[$row['status']] ?? 'secondary' }}">{{ ucfirst($row['status']) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-muted text-center py-4">No settlement records found</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if($mappings->hasPages())
                <div class="report-pagination">
                    {{ $mappings->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
