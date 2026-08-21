@extends('layouts.app')

@section('title', 'Payables Outstanding')

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
        border-color: rgba(161, 98, 7, 0.35);
        box-shadow: 0 6px 16px rgba(31, 41, 55, 0.08);
        transform: translateY(-1px);
    }

    .aging-bucket-card.is-active {
        border-color: #a16207;
        background: rgba(161, 98, 7, 0.06);
        box-shadow: 0 0 0 1px #a16207 inset;
    }

    .aging-bucket-card.is-active .label {
        color: #a16207;
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
        color: #a16207;
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
                <span class="report-eyebrow"><i class="bi bi-people-fill"></i> Party Outstanding</span>
                <h1 class="report-title">Payables Outstanding</h1>
                <p class="report-subtitle">Creditors with credit balances on their linked ledgers (Accounts Payable).</p>
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
            <a href="{{ route('admin.reports.creditors-outstanding') }}" class="report-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            <button type="button" class="report-filter-toggle" aria-expanded="false" aria-label="Toggle filters"><i class="bi bi-chevron-down"></i></button>
        </div>
        <form method="GET" action="{{ route('admin.reports.creditors-outstanding') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-5 col-lg-3">
                <label class="form-label">Party</label>
                <select name="party_id" class="form-select">
                    <option value="">All Parties</option>
                    @foreach($parties as $party)
                        <option value="{{ $party['id'] }}" {{ (string) ($partyId ?? '') === (string) $party['id'] ? 'selected' : '' }}>{{ $party['text'] }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">As of Date</label>
                <input type="date" name="as_of_date" class="form-control" value="{{ $asOfDate ?? '' }}">
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
            <div class="col-6 col-md-3 col-lg-2">
                <label class="form-label">Bucket Basis</label>
                <select name="basis" class="form-select" data-searchable="false">
                    <option value="due" {{ ($report['filters']['basis'] ?? 'due') === 'due' ? 'selected' : '' }}>Due Days</option>
                    <option value="billed" {{ ($report['filters']['basis'] ?? 'due') === 'billed' ? 'selected' : '' }}>Billed Days</option>
                </select>
            </div>
            <div class="col-12 col-lg-auto report-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
                <div class="btn-group report-export-dropdown">
                    <button type="button" class="btn report-btn-export-neutral dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-download"></i>Export
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="{{ route('admin.export.excel', ['type' => 'creditors', 'financial_year_id' => $financialYearId, 'as_of_date' => $asOfDate, 'overdue_status' => $report['filters']['overdue_status'] ?? 'all', 'age_bucket' => $report['filters']['age_bucket'] ?? 'all', 'age_min' => $report['filters']['age_min'] ?? '', 'age_max' => $report['filters']['age_max'] ?? '', 'basis' => $report['filters']['basis'] ?? 'due']) }}"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Excel</a></li>
                        <li><a class="dropdown-item" href="{{ route('admin.export.creditors-outstanding.pdf', ['financial_year_id' => $financialYearId, 'as_of_date' => $asOfDate, 'overdue_status' => $report['filters']['overdue_status'] ?? 'all', 'age_bucket' => $report['filters']['age_bucket'] ?? 'all', 'age_min' => $report['filters']['age_min'] ?? '', 'age_max' => $report['filters']['age_max'] ?? '', 'basis' => $report['filters']['basis'] ?? 'due']) }}"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>

   

    <div class="report-stats-grid">
        <div class="report-stat report-stat--warning">
            <p class="report-stat-label">Total Outstanding</p>
            <h3 class="report-stat-value">₹{{ number_format($report['total'], 2) }}</h3>
            <p class="report-stat-note">Open payables across all creditors.</p>
        </div>
        <div class="report-stat report-stat--info">
            <p class="report-stat-label">Invoices</p>
            <h3 class="report-stat-value">{{ $creditors->total() }}</h3>
            <p class="report-stat-note">Outstanding invoices from all creditors.</p>
        </div>
    </div>

    {{--
    Cards section (aging bucket cards) — commented out per request; the underlying
    data (aging_summary) is still computed above so this can be restored easily.
    <div class="aging-bucket-grid">
        @php $activeBucket = $report['filters']['age_bucket'] ?? 'all'; @endphp
        @foreach(($report['aging_summary'] ?? []) as $bucketKey => $bucket)
            <a href="{{ route('admin.reports.creditors-outstanding', array_filter([
                'financial_year_id' => $financialYearId,
                'as_of_date' => $asOfDate,
                'overdue_status' => $report['filters']['overdue_status'] ?? 'all',
                'age_bucket' => $bucketKey,
                'party_id' => $partyId ?? '',
                'basis' => $report['filters']['basis'] ?? 'due',
            ])) }}" class="aging-bucket-card {{ $activeBucket === $bucketKey ? 'is-active' : '' }}">
                <p class="label">{{ $bucket['label'] }}</p>
                <p class="count">{{ $bucket['count'] }} Invoices</p>
                <p class="amount">₹{{ number_format((float) $bucket['amount'], 2) }}</p>
            </a>
        @endforeach
    </div>
    --}}

    <div class="report-panel">
        <div class="report-panel-header report-panel-header--tabs">
            <ul class="nav nav-tabs report-tabs" id="creditorsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="invoice-wise-tab" data-bs-toggle="tab" data-bs-target="#invoice-wise-pane" type="button" role="tab" aria-controls="invoice-wise-pane" aria-selected="true">
                        <i class="bi bi-receipt me-1"></i>Invoice Wise Details
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="party-wise-tab" data-bs-toggle="tab" data-bs-target="#party-wise-pane" type="button" role="tab" aria-controls="party-wise-pane" aria-selected="false">
                        <i class="bi bi-person-lines-fill me-1"></i>Party Wise Summary
                    </button>
                </li>
            </ul>
            <span class="report-pill report-pill--info">As of @istDate($asOfDate)</span>
            <span class="report-pill report-pill--info"><i class="bi bi-sliders me-1"></i>Bucketed by: {{ ($report['filters']['basis'] ?? 'due') === 'billed' ? 'Billed Days' : 'Due Days' }}</span>
        </div>
        <div class="tab-content" id="creditorsTabsContent">
        <div class="tab-pane fade show active" id="invoice-wise-pane" role="tabpanel" aria-labelledby="invoice-wise-tab">
        <div class="report-panel-body report-panel-body--flush">
            <div class="report-table-tools">
                <form method="GET" action="{{ route('admin.reports.creditors-outstanding') }}" class="report-rows-form">
                    <input type="hidden" name="as_of_date" value="{{ $asOfDate ?? '' }}">
                    <input type="hidden" name="overdue_status" value="{{ $report['filters']['overdue_status'] ?? 'all' }}">
                    <input type="hidden" name="age_bucket" value="{{ $report['filters']['age_bucket'] ?? 'all' }}">
                    <input type="hidden" name="age_min" value="{{ $report['filters']['age_min'] ?? '' }}">
                    <input type="hidden" name="age_max" value="{{ $report['filters']['age_max'] ?? '' }}">
                    <input type="hidden" name="basis" value="{{ $report['filters']['basis'] ?? 'due' }}">
                    <input type="hidden" name="party_id" value="{{ $partyId ?? '' }}">
                    <label for="creditors-per-page" class="report-rows-label">Rows Per Page</label>
                    <select id="creditors-per-page" name="per_page" class="form-select form-select-sm report-rows-select" onchange="this.form.submit()">
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
                        <th class="text-end">Balance (₹) Cr</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($creditors as $item)
                    <tr>
                        <td>{{ ($creditors->firstItem() ?? 1) + $loop->index }}</td>
                        <td>
                            @if(!empty($item['is_opening_balance']))
                                <span class="fw-semibold text-muted" title="Opening balance reference">{{ $item['invoice_number'] }}</span>
                            @else
                                <a href="{{ route('admin.purchase-invoices.show', $item['invoice_id']) }}" class="report-detail-link" title="View invoice">
                                    {{ $item['invoice_number'] }}
                                </a>
                            @endif
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
                        <td class="text-end fw-bold text-warning">₹{{ number_format($item['balance'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-muted text-center py-4">No outstanding payables</td></tr>
                    @endforelse
                </tbody>
                @if($creditors->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="9">Total Outstanding</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['total'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
            @if($creditors->hasPages())
                <div class="report-pagination">
                    {{ $creditors->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
        </div>

        <div class="tab-pane fade" id="party-wise-pane" role="tabpanel" aria-labelledby="party-wise-tab">
        <div class="report-panel-body report-panel-body--flush">
            <div class="report-table-tools">
                <form method="GET" action="{{ route('admin.reports.creditors-outstanding') }}#party-wise-pane" class="report-rows-form">
                    <input type="hidden" name="as_of_date" value="{{ $asOfDate ?? '' }}">
                    <input type="hidden" name="overdue_status" value="{{ $report['filters']['overdue_status'] ?? 'all' }}">
                    <input type="hidden" name="age_bucket" value="{{ $report['filters']['age_bucket'] ?? 'all' }}">
                    <input type="hidden" name="age_min" value="{{ $report['filters']['age_min'] ?? '' }}">
                    <input type="hidden" name="age_max" value="{{ $report['filters']['age_max'] ?? '' }}">
                    <input type="hidden" name="basis" value="{{ $report['filters']['basis'] ?? 'due' }}">
                    <input type="hidden" name="party_id" value="{{ $partyId ?? '' }}">
                    <label for="creditors-party-per-page" class="report-rows-label">Rows Per Page</label>
                    <select id="creditors-party-per-page" name="party_per_page" class="form-select form-select-sm report-rows-select" onchange="this.form.submit()">
                        @foreach([10, 25, 50, 100] as $size)
                            <option value="{{ $size }}" {{ (int) request('party_per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                        @endforeach
                        <option value="all" {{ strtolower((string) request('party_per_page', 10)) === 'all' ? 'selected' : '' }}>All</option>
                    </select>
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
                        <th class="text-end">Balance (₹) Cr</th>
                        <th class="text-end">Unapplied (₹)</th>
                        <th class="text-end">Max Due Days</th>
                        <th></th>
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
                        <td class="text-end fw-bold text-warning">₹{{ number_format($item['balance'], 2) }}</td>
                        <td class="text-end">
                            @if(($item['unapplied_amount'] ?? 0) > 0)
                                <span class="fw-semibold text-success">₹{{ number_format($item['unapplied_amount'], 2) }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            @if($item['max_due_days'] === null)
                                <span class="text-muted">-</span>
                            @elseif($item['max_due_days'] > 0)
                                <span class="text-danger fw-semibold">{{ $item['max_due_days'] }}</span>
                            @else
                                <span class="text-success">{{ $item['max_due_days'] }}</span>
                            @endif
                        </td>
                        <td>
                            @if(($item['opening_balance_available'] ?? 0) > 0 || ($item['unapplied_amount'] ?? 0) > 0)
                                <button type="button" class="btn btn-sm btn-outline-success apply-unapplied-btn"
                                        data-party-id="{{ $item['party']->id }}"
                                        data-party-name="{{ $item['party']->name }}"
                                        data-invoice-type="purchase"
                                        data-unapplied="{{ $item['unapplied_amount'] }}"
                                        data-opening-balance="{{ $item['opening_balance_available'] ?? 0 }}">
                                    <i class="bi bi-arrow-repeat me-1"></i>Apply
                                </button>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-muted text-center py-4">No outstanding payables</td></tr>
                    @endforelse
                </tbody>
                @if($partyWise->count() > 0)
                <tfoot>
                    <tr>
                        <td colspan="5">Total Outstanding</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['total'], 2) }}</td>
                        <td colspan="3"></td>
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

<div class="modal fade" id="applyUnappliedModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Apply Unapplied Amount</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Party: <strong id="applyUnappliedPartyName"></strong></p>
                <p class="text-muted small mb-3">Available amount to allocate: ₹<span id="applyUnappliedAvailable"></span></p>
                <div class="mb-3">
                    <label class="form-label">Invoice</label>
                    <select id="applyUnappliedInvoice" class="form-select">
                        <option value="">Loading invoices…</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount to Apply</label>
                    <input type="number" id="applyUnappliedAmount" class="form-control" step="0.01" min="0.01">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="applyUnappliedSubmit">Apply</button>
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
        document.querySelectorAll('#creditorsTabs button[data-bs-toggle="tab"]').forEach((tabEl) => {
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

        const applyUnappliedModalEl = document.getElementById('applyUnappliedModal');
        const applyUnappliedModal = applyUnappliedModalEl ? new bootstrap.Modal(applyUnappliedModalEl) : null;
        let applyUnappliedContext = null;

        $(document).on('click', '.apply-unapplied-btn', function () {
            const $btn = $(this);
            applyUnappliedContext = {
                partyId: $btn.data('party-id'),
                invoiceType: $btn.data('invoice-type'),
                unapplied: parseFloat($btn.data('unapplied')) || 0,
                openingBalance: parseFloat($btn.data('opening-balance')) || 0,
            };
            applyUnappliedContext.source = applyUnappliedContext.openingBalance > 0 ? 'opening_balance' : 'unapplied';
            const available = applyUnappliedContext.source === 'opening_balance'
                ? applyUnappliedContext.openingBalance
                : applyUnappliedContext.unapplied;

            $('#applyUnappliedPartyName').text($btn.data('party-name'));
            $('#applyUnappliedAvailable').text(available.toFixed(2));
            $('#applyUnappliedAmount').val(available.toFixed(2)).attr('max', available);
            $('#applyUnappliedInvoice').html('<option value="">Loading invoices…</option>');

            $.get(`/admin/parties/${applyUnappliedContext.partyId}/outstanding-invoices`, { invoice_type: applyUnappliedContext.invoiceType })
                .done(function (response) {
                    const invoices = (response && response.data) || [];
                    if (!invoices.length) {
                        $('#applyUnappliedInvoice').html('<option value="">No outstanding invoices</option>');
                        return;
                    }
                    let options = '<option value="">Select invoice</option>';
                    invoices.forEach((inv) => {
                        options += `<option value="${inv.id}" data-balance="${inv.balance_due}">${inv.invoice_number} — Balance: ₹${inv.balance_due.toFixed(2)}</option>`;
                    });
                    $('#applyUnappliedInvoice').html(options);
                })
                .fail(function () {
                    $('#applyUnappliedInvoice').html('<option value="">Could not load invoices</option>');
                });

            applyUnappliedModal?.show();
        });

        $(document).on('change', '#applyUnappliedInvoice', function () {
            const balance = parseFloat($(this).find(':selected').data('balance')) || 0;
            if (balance > 0 && applyUnappliedContext) {
                const available = applyUnappliedContext.source === 'opening_balance'
                    ? applyUnappliedContext.openingBalance
                    : applyUnappliedContext.unapplied;
                const cap = Math.min(balance, available);
                $('#applyUnappliedAmount').val(cap.toFixed(2));
            }
        });

        $('#applyUnappliedSubmit').on('click', function () {
            if (!applyUnappliedContext) return;

            const invoiceId = $('#applyUnappliedInvoice').val();
            const amount = parseFloat($('#applyUnappliedAmount').val());

            if (!invoiceId) {
                toastr.error('Please select an invoice.');
                return;
            }
            if (!amount || amount <= 0) {
                toastr.error('Please enter a valid amount.');
                return;
            }

            $.ajax({
                url: `/admin/parties/${applyUnappliedContext.partyId}/apply-unapplied`,
                type: 'POST',
                data: { invoice_id: invoiceId, amount: amount, source: applyUnappliedContext.source },
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (r) {
                    toastr.success(r.message);
                    applyUnappliedModal?.hide();
                    setTimeout(() => window.location.reload(), 800);
                },
                error: function (xhr) {
                    toastr.error(xhr.responseJSON?.message || 'Error applying unapplied amount');
                }
            });
        });
    });
</script>
@endpush
@endsection
