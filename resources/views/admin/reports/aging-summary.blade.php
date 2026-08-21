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

    .report-filter-card .custom-age-input {
        min-height: 38px;
        height: 38px;
        padding: 0.35rem 0.5rem;
        font-size: 0.85rem;
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
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Accounting Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <div class="report-filter-head">
            <span class="report-filter-head-title"><i class="bi bi-funnel"></i> Filters</span>
            <a href="{{ route('admin.reports.aging-summary') }}" class="report-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            <button type="button" class="report-filter-toggle" aria-expanded="false" aria-label="Toggle filters"><i class="bi bi-chevron-down"></i></button>
        </div>
        <form method="GET" action="{{ route('admin.reports.aging-summary') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-5 col-lg-3">
                <label class="form-label">Financial Year</label>
                <select name="financial_year_id" class="form-select" data-searchable="false">
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" {{ (string) ($financialYearId ?? '') === (string) $fy->id ? 'selected' : '' }}>
                            {{ $fy->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom ?? '' }}">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo ?? '' }}">
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">Overdue Status</label>
                <select name="overdue_status" class="form-select" data-searchable="false">
                    <option value="all" {{ ($filters['overdue_status'] ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                    <option value="due" {{ ($filters['overdue_status'] ?? '') === 'due' ? 'selected' : '' }}>Due</option>
                    <option value="not_due" {{ ($filters['overdue_status'] ?? '') === 'not_due' ? 'selected' : '' }}>Not Due</option>
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">Aging Bucket</label>
                <select name="age_bucket" id="age-bucket-select" class="form-select" data-searchable="false">
                    <option value="all" {{ ($filters['age_bucket'] ?? 'all') === 'all' ? 'selected' : '' }}>All Buckets</option>
                    <option value="current" {{ ($filters['age_bucket'] ?? '') === 'current' ? 'selected' : '' }}>Current</option>
                    <option value="1_30" {{ ($filters['age_bucket'] ?? '') === '1_30' ? 'selected' : '' }}>1-30 Days</option>
                    <option value="31_60" {{ ($filters['age_bucket'] ?? '') === '31_60' ? 'selected' : '' }}>31-60 Days</option>
                    <option value="61_90" {{ ($filters['age_bucket'] ?? '') === '61_90' ? 'selected' : '' }}>61-90 Days</option>
                    <option value="91_plus" {{ ($filters['age_bucket'] ?? '') === '91_plus' ? 'selected' : '' }}>91+ Days</option>
                    <option value="custom" {{ ($filters['age_bucket'] ?? '') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
                <div id="custom-age-range" class="custom-age-range">
                    <input type="number" name="age_min" class="form-control custom-age-input" placeholder="Min" min="0" value="{{ $filters['age_min'] ?? '' }}">
                    <input type="number" name="age_max" class="form-control custom-age-input" placeholder="Max" min="0" value="{{ $filters['age_max'] ?? '' }}">
                </div>
            </div>
            <div class="col-12 col-lg-auto report-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <div class="btn-group report-export-dropdown">
                    <button type="button" class="btn report-btn-export-neutral dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i>Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.export.excel', ['type' => 'aging-summary', 'financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'overdue_status' => $filters['overdue_status'] ?? 'all', 'age_bucket' => $filters['age_bucket'] ?? 'all', 'age_min' => $filters['age_min'] ?? '', 'age_max' => $filters['age_max'] ?? '']) }}"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Excel</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.export.aging-summary.pdf', ['financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'overdue_status' => $filters['overdue_status'] ?? 'all', 'age_bucket' => $filters['age_bucket'] ?? 'all', 'age_min' => $filters['age_min'] ?? '', 'age_max' => $filters['age_max'] ?? '']) }}"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF</a></li>
                    </ul>
                </div>
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
                        <p class="count">{{ $bucket['count'] }} Invoices</p>
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
                        <p class="count">{{ $bucket['count'] }} Invoices</p>
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
                    <input type="hidden" name="age_min" value="{{ $filters['age_min'] ?? '' }}">
                    <input type="hidden" name="age_max" value="{{ $filters['age_max'] ?? '' }}">
                    <label for="aging-per-page" class="report-rows-label">Rows Per Page</label>
                    <select id="aging-per-page" name="per_page" class="form-select form-select-sm report-rows-select" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                        <option value="all" {{ strtolower((string) request('per_page', 10)) === 'all' ? 'selected' : '' }}>All</option>
                    </select>
                </form>
            </div>
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Type</th>
                        <th>Invoice No</th>
                        <th>Party</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th class="text-end">Billed Days</th>
                        <th class="text-end">Due Days</th>
                        <th class="text-end">Amount (₹)</th>
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
                            <td>
                                <a href="{{ route(($item['report_type'] ?? '') === 'Receivable' ? 'admin.sales-invoices.show' : 'admin.purchase-invoices.show', $item['invoice_id']) }}" class="report-detail-link" title="View invoice">
                                    {{ $item['invoice_number'] }}
                                </a>
                                <span class="text-muted small">/ {{ $item['party']->party_code }}</span>
                            </td>
                            <td class="fw-semibold">{{ $item['party']->name ?? '-' }}</td>
                            <td>{{ !empty($item['invoice_date']) ? \Carbon\Carbon::parse($item['invoice_date'])->format('d/m/Y') : '-' }}</td>
                            <td>{{ !empty($item['due_date']) ? \Carbon\Carbon::parse($item['due_date'])->format('d/m/Y') : '-' }}</td>
                            <td class="text-end" title="Days since billed date">{{ $item['billed_days'] ?? '-' }}</td>
                            <td class="text-end" title="Days since due date (negative = not yet due)">
                                @if(($item['due_days'] ?? null) === null)
                                    <span class="text-muted">-</span>
                                @elseif($item['due_days'] > 0)
                                    <span class="text-danger fw-semibold">{{ $item['due_days'] }}</span>
                                @elseif($item['due_days'] === 0)
                                    <span class="text-warning fw-semibold">0</span>
                                @else
                                    <span class="text-success">{{ $item['due_days'] }}</span>
                                @endif
                            </td>
                            <td class="text-end">₹{{ number_format((float) ($item['invoice_total'] ?? 0), 2) }}</td>
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
@push('scripts')
<script>
    $(function () {
        const $select = $('#age-bucket-select');
        const $range = $('#custom-age-range');
        if (!$select.length || !$range.length) return;

        const toggle = () => {
            $range.toggleClass('is-visible', $select.val() === 'custom');
        };

        $(document).on('change', '#age-bucket-select', toggle);
        toggle();
    });
</script>
@endpush
@endsection
