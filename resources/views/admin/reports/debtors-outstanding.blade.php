@extends('layouts.app')

@section('title', 'Receivables Outstanding')

@include('admin.reports._theme')

@push('styles')
<style>
    .aging-bucket-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.85rem;
    }

    .aging-bucket-card {
        display: block;
        border: 1px solid rgba(31, 41, 55, 0.08);
        border-radius: 16px;
        padding: 0.85rem 0.95rem;
        background: #ffffff;
        text-decoration: none;
        cursor: pointer;
        transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
    }

    .aging-bucket-card:hover {
        border-color: rgba(185, 28, 28, 0.35);
        box-shadow: 0 6px 16px rgba(31, 41, 55, 0.08);
        transform: translateY(-1px);
    }

    .aging-bucket-card.is-active {
        border-color: #b91c1c;
        background: rgba(185, 28, 28, 0.06);
        box-shadow: 0 0 0 1px #b91c1c inset;
    }

    .aging-bucket-card.is-active .label {
        color: #b91c1c;
    }

    .party-wise-filter-tools {
        width: 100%;
    }

    .party-wise-filter-tools .form-label {
        font-size: 0.78rem;
        font-weight: 600;
        color: #7c8298;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .aging-bucket-card .label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #7c8298;
        margin-bottom: 0.35rem;
    }

    .aging-bucket-card .count {
        font-size: 1.1rem;
        font-weight: 700;
        color: #1f2937;
    }

    .aging-bucket-card .amount {
        font-size: 0.88rem;
        color: #b91c1c;
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
                <span class="report-eyebrow"><i class="bi bi-people"></i> Party Outstanding</span>
                <h1 class="report-title">Receivables Outstanding</h1>
                <p class="report-subtitle">Debtors with debit balances on their linked ledgers (Accounts Receivable).</p>
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
            <a href="{{ route('admin.reports.debtors-outstanding') }}" class="report-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            <button type="button" class="report-filter-toggle" aria-expanded="false" aria-label="Toggle filters"><i class="bi bi-chevron-down"></i></button>
        </div>
        <form method="GET" action="{{ route('admin.reports.debtors-outstanding') }}" class="row g-3 align-items-end">
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
                    <option value="all" {{ ($report['filters']['overdue_status'] ?? 'all') === 'all' ? 'selected' : '' }}>All</option>
                    <option value="due" {{ ($report['filters']['overdue_status'] ?? '') === 'due' ? 'selected' : '' }}>Due</option>
                    <option value="not_due" {{ ($report['filters']['overdue_status'] ?? '') === 'not_due' ? 'selected' : '' }}>Not Due</option>
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">Aging Bucket</label>
                <select name="age_bucket" id="age-bucket-select" class="form-select" data-searchable="false">
                    <option value="all" {{ ($report['filters']['age_bucket'] ?? 'all') === 'all' ? 'selected' : '' }}>All Buckets</option>
                    <option value="current" {{ ($report['filters']['age_bucket'] ?? '') === 'current' ? 'selected' : '' }}>Current</option>
                    <option value="1_30" {{ ($report['filters']['age_bucket'] ?? '') === '1_30' ? 'selected' : '' }}>1-30 Days</option>
                    <option value="31_60" {{ ($report['filters']['age_bucket'] ?? '') === '31_60' ? 'selected' : '' }}>31-60 Days</option>
                    <option value="61_90" {{ ($report['filters']['age_bucket'] ?? '') === '61_90' ? 'selected' : '' }}>61-90 Days</option>
                    <option value="91_plus" {{ ($report['filters']['age_bucket'] ?? '') === '91_plus' ? 'selected' : '' }}>91+ Days</option>
                    <option value="custom" {{ ($report['filters']['age_bucket'] ?? '') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
                <div id="custom-age-range" class="custom-age-range">
                    <input type="number" name="age_min" class="form-control custom-age-input" placeholder="Min" min="0" value="{{ $report['filters']['age_min'] ?? '' }}">
                    <input type="number" name="age_max" class="form-control custom-age-input" placeholder="Max" min="0" value="{{ $report['filters']['age_max'] ?? '' }}">
                </div>
            </div>
            <div class="col-12 col-lg-auto report-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <div class="btn-group report-export-dropdown">
                    <button type="button" class="btn report-btn-export-neutral dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i>Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.export.excel', ['type' => 'debtors', 'financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'overdue_status' => $report['filters']['overdue_status'] ?? 'all', 'age_bucket' => $report['filters']['age_bucket'] ?? 'all', 'age_min' => $report['filters']['age_min'] ?? '', 'age_max' => $report['filters']['age_max'] ?? '']) }}"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Excel</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.export.debtors-outstanding.pdf', ['financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'overdue_status' => $report['filters']['overdue_status'] ?? 'all', 'age_bucket' => $report['filters']['age_bucket'] ?? 'all', 'age_min' => $report['filters']['age_min'] ?? '', 'age_max' => $report['filters']['age_max'] ?? '']) }}"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>

   

    <div class="report-stats-grid">
        <div class="report-stat report-stat--danger">
            <p class="report-stat-label">Total Outstanding</p>
            <h3 class="report-stat-value">₹{{ number_format($report['total'], 2) }}</h3>
            <p class="report-stat-note">Open receivables from all debtors.</p>
        </div>
        <div class="report-stat report-stat--info">
            <p class="report-stat-label">Invoices</p>
            <h3 class="report-stat-value">{{ $debtors->total() }}</h3>
            <p class="report-stat-note">Outstanding invoices from all debtors.</p>
        </div>
    </div>

    <div class="aging-bucket-grid">
        @php $activeBucket = $report['filters']['age_bucket'] ?? 'all'; @endphp
        @foreach(($report['aging_summary'] ?? []) as $bucketKey => $bucket)
            <a href="{{ route('admin.reports.debtors-outstanding', array_filter([
                'financial_year_id' => $financialYearId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'overdue_status' => $report['filters']['overdue_status'] ?? 'all',
                'age_bucket' => $bucketKey,
            ])) }}" class="aging-bucket-card {{ $activeBucket === $bucketKey ? 'is-active' : '' }}">
                <p class="label">{{ $bucket['label'] }}</p>
                <p class="count">{{ $bucket['count'] }} Invoices</p>
                <p class="amount">₹{{ number_format((float) $bucket['amount'], 2) }}</p>
            </a>
        @endforeach
    </div>

    <div class="report-panel">
        <div class="report-panel-header report-panel-header--tabs">
            <ul class="nav nav-tabs report-tabs" id="debtorsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="invoice-wise-tab" data-bs-toggle="tab" data-bs-target="#invoice-wise-pane" type="button" role="tab" aria-controls="invoice-wise-pane" aria-selected="true">
                        <i class="bi bi-receipt me-1"></i>Invoice Wise Summary
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="party-wise-tab" data-bs-toggle="tab" data-bs-target="#party-wise-pane" type="button" role="tab" aria-controls="party-wise-pane" aria-selected="false">
                        <i class="bi bi-person-lines-fill me-1"></i>Party Wise Summary
                    </button>
                </li>
            </ul>
            <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
        </div>
        <div class="tab-content" id="debtorsTabsContent">
        <div class="tab-pane fade show active" id="invoice-wise-pane" role="tabpanel" aria-labelledby="invoice-wise-tab">
        <div class="report-panel-body report-panel-body--flush">
            <div class="report-table-tools">
                <form method="GET" action="{{ route('admin.reports.debtors-outstanding') }}" class="report-rows-form">
                    <input type="hidden" name="financial_year_id" value="{{ $financialYearId ?? '' }}">
                    <input type="hidden" name="date_from" value="{{ $dateFrom ?? '' }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo ?? '' }}">
                    <input type="hidden" name="overdue_status" value="{{ $report['filters']['overdue_status'] ?? 'all' }}">
                    <input type="hidden" name="age_bucket" value="{{ $report['filters']['age_bucket'] ?? 'all' }}">
                    <input type="hidden" name="age_min" value="{{ $report['filters']['age_min'] ?? '' }}">
                    <input type="hidden" name="age_max" value="{{ $report['filters']['age_max'] ?? '' }}">
                    <label for="debtors-per-page" class="report-rows-label">Rows Per Page</label>
                    <select id="debtors-per-page" name="per_page" class="form-select form-select-sm report-rows-select" onchange="this.form.submit()">
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
                        <th>Invoice No</th>
                        <th>Party</th>
                        <th>Invoice Date</th>
                        <th>Due Date</th>
                        <th class="text-end">Billed Days</th>
                        <th class="text-end">Due Days</th>
                        <th class="text-end">Amount (₹)</th>
                        <th class="text-end">Paid (₹)</th>
                        <th class="text-end">Balance (₹) Dr</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($debtors as $item)
                    <tr>
                        <td>{{ ($debtors->firstItem() ?? 1) + $loop->index }}</td>
                        <td>
                            <a href="{{ route('admin.sales-invoices.show', $item['invoice_id']) }}" class="report-detail-link" title="View invoice">
                                {{ $item['invoice_number'] }}
                            </a>
                            <span class="text-muted small">/ {{ $item['party']->party_code }}</span>
                        </td>
                        <td class="fw-semibold">
                            @permission('parties.view')
                                <a href="{{ route('admin.parties.show', $item['party']->id) }}" class="report-detail-link" title="View party history">
                                    {{ $item['party']->name }}
                                </a>
                            @else
                                {{ $item['party']->name }}
                            @endpermission
                        </td>
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
                        <td class="text-end">₹{{ number_format((float) ($item['amount_paid'] ?? 0), 2) }}</td>
                        <td class="text-end fw-bold text-danger">₹{{ number_format($item['balance'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-muted text-center py-4">No outstanding receivables</td></tr>
                    @endforelse
                </tbody>
                @if($debtors->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="9">Total Outstanding</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['total'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
            @if($debtors->hasPages())
                <div class="report-pagination">
                    {{ $debtors->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
        </div>

        <div class="tab-pane fade" id="party-wise-pane" role="tabpanel" aria-labelledby="party-wise-tab">
        <div class="report-panel-body report-panel-body--flush">
            <div class="report-table-tools party-wise-filter-tools">
                <form method="GET" action="{{ route('admin.reports.debtors-outstanding') }}#party-wise-pane" class="row g-2 align-items-end w-100">
                    <input type="hidden" name="financial_year_id" value="{{ $financialYearId ?? '' }}">
                    <input type="hidden" name="date_from" value="{{ $dateFrom ?? '' }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo ?? '' }}">
                    <input type="hidden" name="overdue_status" value="{{ $report['filters']['overdue_status'] ?? 'all' }}">
                    <input type="hidden" name="age_bucket" value="{{ $report['filters']['age_bucket'] ?? 'all' }}">
                    <input type="hidden" name="age_min" value="{{ $report['filters']['age_min'] ?? '' }}">
                    <input type="hidden" name="age_max" value="{{ $report['filters']['age_max'] ?? '' }}">
                    <div class="col-12 col-md-6 col-lg-4">
                        <label for="debtors-party-filter" class="form-label">Party</label>
                        <select id="debtors-party-filter" name="party_id" class="form-select">
                            <option value="">All Parties</option>
                            @foreach($parties as $party)
                                <option value="{{ $party['id'] }}" {{ (string) ($partyId ?? '') === (string) $party['id'] ? 'selected' : '' }}>{{ $party['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <label for="debtors-party-per-page" class="form-label">Rows Per Page</label>
                        <select id="debtors-party-per-page" name="party_per_page" class="form-select form-select-sm report-rows-select" onchange="this.form.submit()">
                            @foreach([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" {{ (int) request('party_per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                            @endforeach
                            <option value="all" {{ strtolower((string) request('party_per_page', 10)) === 'all' ? 'selected' : '' }}>All</option>
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                    </div>
                    @if(!empty($partyId))
                    <div class="col-auto">
                        <a href="{{ route('admin.reports.debtors-outstanding', array_filter([
                            'financial_year_id' => $financialYearId,
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'overdue_status' => $report['filters']['overdue_status'] ?? 'all',
                            'age_bucket' => $report['filters']['age_bucket'] ?? 'all',
                            'age_min' => $report['filters']['age_min'] ?? '',
                            'age_max' => $report['filters']['age_max'] ?? '',
                        ])) }}#party-wise-pane" class="btn btn-outline-secondary"><i class="bi bi-x-circle me-1"></i>Clear Party</a>
                    </div>
                    @endif
                </form>
            </div>
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Party</th>
                        <th class="text-end">Invoices</th>
                        <th class="text-end">Amount (₹)</th>
                        <th class="text-end">Paid (₹)</th>
                        <th class="text-end">Balance (₹) Dr</th>
                        <th class="text-end">Max Due Days</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($partyWise as $item)
                    <tr>
                        <td>{{ ($partyWise->firstItem() ?? 1) + $loop->index }}</td>
                        <td class="fw-semibold">
                            @permission('parties.view')
                                <a href="{{ route('admin.parties.show', $item['party']->id) }}" class="report-detail-link" title="View party history">
                                    {{ $item['party']->name }}
                                </a>
                            @else
                                {{ $item['party']->name }}
                            @endpermission
                            <span class="text-muted small">/ {{ $item['party']->party_code }}</span>
                        </td>
                        <td class="text-end">{{ $item['invoice_count'] }}</td>
                        <td class="text-end">₹{{ number_format($item['invoice_total'], 2) }}</td>
                        <td class="text-end">₹{{ number_format($item['amount_paid'], 2) }}</td>
                        <td class="text-end fw-bold text-danger">₹{{ number_format($item['balance'], 2) }}</td>
                        <td class="text-end">
                            @if($item['max_due_days'] === null)
                                <span class="text-muted">-</span>
                            @elseif($item['max_due_days'] > 0)
                                <span class="text-danger fw-semibold">{{ $item['max_due_days'] }}</span>
                            @else
                                <span class="text-success">{{ $item['max_due_days'] }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-muted text-center py-4">No outstanding receivables</td></tr>
                    @endforelse
                </tbody>
                @if($partyWise->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="5">Total Outstanding</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['total'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
            @if($partyWise->hasPages())
                <div class="report-pagination">
                    {{ $partyWise->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
        </div>
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

        // Select2 miscalculates width inside a hidden tab pane; fix on show.
        document.querySelectorAll('#debtorsTabs button[data-bs-toggle="tab"]').forEach((tabEl) => {
            tabEl.addEventListener('shown.bs.tab', function (e) {
                const $pane = $($(e.target).data('bs-target'));
                $pane.find('select').each(function () {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
                initSearchableSelects($pane);
            });
        });

        // Re-open the Party Wise Summary tab after a filter/clear round trip.
        if (window.location.hash === '#party-wise-pane') {
            const partyTabEl = document.getElementById('party-wise-tab');
            if (partyTabEl && window.bootstrap) {
                new bootstrap.Tab(partyTabEl).show();
            }
        }
    });
</script>
@endpush
@endsection
