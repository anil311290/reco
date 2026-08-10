@extends('layouts.app')

@section('title', 'Aging Summary')

@include('admin.reports._theme')

@push('styles')
<style>
    .aging-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 0.85rem;
    }

    .aging-summary-card {
        border: 1px solid rgba(31, 41, 55, 0.08);
        border-radius: 16px;
        padding: 0.9rem 1rem;
        background: #ffffff;
    }

    .aging-summary-label {
        font-size: 0.76rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #7c8298;
        margin-bottom: 0.35rem;
    }

    .aging-summary-value {
        margin: 0;
        font-size: 1.18rem;
        font-weight: 700;
        color: #1f2937;
    }

    .aging-bucket-wrap {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.85rem;
    }

    .aging-bucket-card {
        border: 1px solid rgba(31, 41, 55, 0.08);
        border-radius: 16px;
        padding: 0.85rem 0.95rem;
        background: #ffffff;
    }

    .aging-bucket-card .label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #7c8298;
        margin-bottom: 0.35rem;
    }

    .aging-bucket-card .count {
        margin: 0;
        font-size: 1.02rem;
        font-weight: 700;
        color: #1f2937;
    }

    .aging-bucket-card .amount {
        margin: 0.2rem 0 0;
        font-size: 0.88rem;
        color: #374151;
        font-weight: 600;
    }
</style>
@endpush

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-hourglass-split"></i> Outstanding Aging</span>
                <h1 class="report-title">Aging Summary</h1>
                <p class="report-subtitle">Combined receivables and payables with overdue duration and aging buckets.</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="{{ route('admin.reports.aging-summary') }}" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">Financial Year</label>
                <select name="financial_year_id" class="form-select">
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" {{ (string) ($financialYearId ?? '') === (string) $fy->id ? 'selected' : '' }}>
                            {{ $fy->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Overdue Status</label>
                <select name="overdue_status" class="form-select">
                    <option value="all" {{ ($filters['overdue_status'] ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                    <option value="overdue" {{ ($filters['overdue_status'] ?? '') === 'overdue' ? 'selected' : '' }}>Overdue Only</option>
                    <option value="current" {{ ($filters['overdue_status'] ?? '') === 'current' ? 'selected' : '' }}>Current Only</option>
                </select>
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">Aging Bucket</label>
                <select name="age_bucket" class="form-select">
                    <option value="all" {{ ($filters['age_bucket'] ?? 'all') === 'all' ? 'selected' : '' }}>All Buckets</option>
                    <option value="current" {{ ($filters['age_bucket'] ?? '') === 'current' ? 'selected' : '' }}>Current</option>
                    <option value="1_30" {{ ($filters['age_bucket'] ?? '') === '1_30' ? 'selected' : '' }}>1-30 Days</option>
                    <option value="31_60" {{ ($filters['age_bucket'] ?? '') === '31_60' ? 'selected' : '' }}>31-60 Days</option>
                    <option value="61_90" {{ ($filters['age_bucket'] ?? '') === '61_90' ? 'selected' : '' }}>61-90 Days</option>
                    <option value="91_plus" {{ ($filters['age_bucket'] ?? '') === '91_plus' ? 'selected' : '' }}>91+ Days</option>
                </select>
            </div>
            <div class="col-lg-auto col-md-12 report-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('admin.export.excel', ['type' => 'aging-summary', 'financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'overdue_status' => $filters['overdue_status'] ?? 'all', 'age_bucket' => $filters['age_bucket'] ?? 'all']) }}" class="btn btn-outline-success report-btn-export">
                    <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                </a>
                <a href="{{ route('admin.export.aging-summary.pdf', ['financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'overdue_status' => $filters['overdue_status'] ?? 'all', 'age_bucket' => $filters['age_bucket'] ?? 'all']) }}" class="btn btn-outline-danger report-btn-export">
                    <i class="bi bi-file-earmark-pdf"></i>PDF
                </a>
            </div>
        </form>
    </div>

    <div class="aging-summary-grid">
        <div class="aging-summary-card">
            <p class="aging-summary-label">Receivables Outstanding</p>
            <p class="aging-summary-value text-danger">₹{{ number_format((float) ($summary['receivables_total'] ?? 0), 2) }}</p>
        </div>
        <div class="aging-summary-card">
            <p class="aging-summary-label">Payables Outstanding</p>
            <p class="aging-summary-value text-warning">₹{{ number_format((float) ($summary['payables_total'] ?? 0), 2) }}</p>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-bar-chart-steps text-primary"></i>Aging Buckets - Receivables</h6>
        </div>
        <div class="report-panel-body">
            <div class="aging-bucket-wrap">
                @foreach(($summary['receivables'] ?? []) as $bucket)
                    <div class="aging-bucket-card">
                        <p class="label">{{ $bucket['label'] }}</p>
                        <p class="count">{{ $bucket['count'] }} Parties</p>
                        <p class="amount">₹{{ number_format((float) $bucket['amount'], 2) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-bar-chart-steps text-warning"></i>Aging Buckets - Payables</h6>
        </div>
        <div class="report-panel-body">
            <div class="aging-bucket-wrap">
                @foreach(($summary['payables'] ?? []) as $bucket)
                    <div class="aging-bucket-card">
                        <p class="label">{{ $bucket['label'] }}</p>
                        <p class="count">{{ $bucket['count'] }} Parties</p>
                        <p class="amount">₹{{ number_format((float) $bucket['amount'], 2) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-list-ul text-primary"></i>Aging Details</h6>
            <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <div class="report-table-tools">
                <form method="GET" action="{{ route('admin.reports.aging-summary') }}" class="report-rows-form">
                    <input type="hidden" name="financial_year_id" value="{{ $financialYearId ?? '' }}">
                    <input type="hidden" name="date_from" value="{{ $dateFrom ?? '' }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo ?? '' }}">
                    <input type="hidden" name="overdue_status" value="{{ $filters['overdue_status'] ?? 'all' }}">
                    <input type="hidden" name="age_bucket" value="{{ $filters['age_bucket'] ?? 'all' }}">
                    <label for="aging-per-page" class="report-rows-label">Rows Per Page</label>
                    <select id="aging-per-page" name="per_page" class="form-select form-select-sm report-rows-select" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Party</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th>Oldest Due Date</th>
                        <th>Overdue By</th>
                        <th class="text-end">Overdue (₹)</th>
                        <th class="text-end">Balance (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($agingRows as $item)
                        <tr>
                            <td>{{ ($agingRows->firstItem() ?? 1) + $loop->index }}</td>
                            <td>
                                @if(($item['report_type'] ?? '') === 'Receivable')
                                    <span class="report-pill report-pill--danger">Receivable</span>
                                @else
                                    <span class="report-pill report-pill--warning">Payable</span>
                                @endif
                            </td>
                            <td class="fw-semibold">{{ $item['party']->name ?? '-' }}</td>
                            <td>{{ $item['party']->mobile ?? '-' }}</td>
                            <td>{{ $item['party']->email ?? '-' }}</td>
                            <td>{{ !empty($item['oldest_due_date']) ? \Carbon\Carbon::parse($item['oldest_due_date'])->format('d/m/Y') : '-' }}</td>
                            <td>
                                @if(($item['overdue_days'] ?? 0) > 0)
                                    <span class="text-danger fw-semibold">{{ $item['overdue_label'] }}</span>
                                @else
                                    <span class="text-success">Current</span>
                                @endif
                            </td>
                            <td class="text-end">₹{{ number_format((float) ($item['overdue_amount'] ?? 0), 2) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format((float) ($item['balance'] ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-muted text-center py-4">No aging records found for selected filters</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if($agingRows->hasPages())
                <div class="report-pagination">
                    {{ $agingRows->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
