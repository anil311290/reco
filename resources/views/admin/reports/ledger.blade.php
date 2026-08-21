@extends('layouts.app')

@section('title', 'Account Ledger')

@include('admin.reports._theme')

@push('styles')
<style>
    .ledger-page .report-stats-grid {
        gap: 0.8rem;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        margin: 8px 0;
    }

    .ledger-page .report-stat {
        padding: 0.95rem 1rem;
        min-height: 132px;
    }

    .ledger-page .report-stat-label {
        font-size: 0.73rem;
    }

    .ledger-page .report-stat-value {
        margin-top: 0.35rem;
        font-size: clamp(1.2rem, 1.8vw, 1.75rem);
        line-height: 1.2;
    }

    .ledger-page .report-stat-note {
        margin-top: 0.28rem;
        font-size: 0.82rem;
        line-height: 1.35;
    }

    .ledger-page .ledger-header-pills {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: 0.45rem;
    }

    .ledger-page .ledger-date-col {
        min-width: 116px;
        white-space: nowrap;
    }

    .ledger-page .ledger-voucher-col {
        min-width: 124px;
        white-space: nowrap;
    }

    .ledger-page .ledger-amount-col {
        min-width: 132px;
        white-space: nowrap;
    }

    .ledger-page .ledger-actions-col {
        min-width: 124px;
        white-space: nowrap;
    }

    .ledger-page .btn-group.btn-group-sm > .btn {
        padding: 0.28rem 0.44rem;
    }

    @media (max-width: 991.98px) {
        .ledger-page .report-stat {
            min-height: 116px;
        }

        .ledger-page .ledger-header-pills {
            justify-content: flex-start;
        }
    }
</style>
@endpush

@section('content')
<div class="reports-shell ledger-page">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-book"></i> Transaction Trail</span>
                <h1 class="report-title">Ledger</h1>
                <p class="report-subtitle">Select one ledger to view opening balance, voucher-wise movement, and closing balance (Tally ledger style).</p>
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
            <a href="{{ route('admin.reports.ledger') }}" class="report-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
            <button type="button" class="report-filter-toggle" aria-expanded="false" aria-label="Toggle filters"><i class="bi bi-chevron-down"></i></button>
        </div>
        <form method="GET" action="{{ route('admin.reports.ledger') }}" class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Account</label>
                <select name="account_id" class="form-select" required>
                    <option value="">Select Ledger</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ (string) $accountId === (string) $account->id ? 'selected' : '' }}>
                            {{ $account->account_code }} - {{ $account->account_name }}
                        </option>
                    @endforeach
                </select>
            </div>
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
                <label class="form-label">From</label>
                <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
            </div>
            <div class="col-lg-2 col-md-3">
                <label class="form-label">To</label>
                <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
            </div>
            <div class="col-lg-auto report-filter-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(!empty($accountId))
                    <div class="btn-group report-export-dropdown">
                        <button type="button" class="btn report-btn-export-neutral dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-download"></i>Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('admin.export.excel', ['type' => 'ledger', 'account_id' => $accountId, 'financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"><i class="bi bi-file-earmark-spreadsheet text-success me-2"></i>Excel</a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.export.ledger.pdf', ['account_id' => $accountId, 'financial_year_id' => $financialYearId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}"><i class="bi bi-file-earmark-pdf text-danger me-2"></i>PDF</a></li>
                        </ul>
                    </div>
                @endif
            </div>
        </form>
    </div>

@if(!$report)
    <div class="report-panel">
        <div class="report-empty">
            <div class="report-empty-icon"><i class="bi bi-journal-text"></i></div>
            <div>
                <h5 class="mb-2">Select an account</h5>
                <p class="mb-0">Choose an account and date window to view the ledger.</p>
            </div>
        </div>
    </div>
@else
    <div class="report-stats-grid">
        <div class="report-stat report-stat--info">
            <p class="report-stat-label">Account</p>
            <h3 class="report-stat-value">{{ $report['account']->account_code }}</h3>
            <p class="report-stat-note">{{ $report['account']->account_name }}</p>
        </div>
        <div class="report-stat {{ $report['opening_balance']['type'] === 'debit' ? 'report-stat--primary' : 'report-stat--danger' }}">
            <p class="report-stat-label">Opening Balance</p>
            <h3 class="report-stat-value">₹{{ number_format($report['opening_balance']['balance'], 2) }}</h3>
            <p class="report-stat-note">@drCr($report['opening_balance']['type'])</p>
        </div>
        <div class="report-stat {{ $report['closing_balance']['type'] === 'debit' ? 'report-stat--success' : 'report-stat--warning' }}">
            <p class="report-stat-label">Closing Balance</p>
            <h3 class="report-stat-value">₹{{ number_format($report['closing_balance']['balance'], 2) }}</h3>
            <p class="report-stat-note">@drCr($report['closing_balance']['type'])</p>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-list-columns-reverse text-primary"></i>Ledger Entries</h6>
            <div class="ledger-header-pills">
                <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
            </div>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <div class="report-table-tools">
                <form method="GET" action="{{ route('admin.reports.ledger') }}" class="report-rows-form">
                    <input type="hidden" name="account_id" value="{{ $accountId }}">
                    <input type="hidden" name="financial_year_id" value="{{ $financialYearId ?? '' }}">
                    <input type="hidden" name="date_from" value="{{ $dateFrom }}">
                    <input type="hidden" name="date_to" value="{{ $dateTo }}">
                    <label for="ledger-per-page" class="report-rows-label">Rows Per Page</label>
                    <select id="ledger-per-page" name="per_page" class="form-select form-select-sm report-rows-select" onchange="this.form.submit()">
                        @foreach([10, 25, 35, 50, 100] as $size)
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
                            <th class="ledger-date-col">Date</th>
                            <th class="ledger-voucher-col">Voucher #</th>
                            <th>Particulars</th>
                            <th>Party</th>
                            <th class="text-end ledger-amount-col">Debit (₹)</th>
                            <th class="text-end ledger-amount-col">Credit (₹)</th>
                            <th class="text-end ledger-amount-col">Balance (₹)</th>
                            <th class="text-center ledger-actions-col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($entries->currentPage() === 1)
                        <tr class="report-row-emphasis">
                            <td class="ledger-date-col">-</td>
                            <td class="ledger-voucher-col">-</td>
                            <td colspan="2" class="fw-semibold">Opening Balance b/f</td>
                            <td class="text-end ledger-amount-col">-</td>
                            <td class="text-end ledger-amount-col">-</td>
                            <td class="text-end fw-bold ledger-amount-col">₹{{ number_format($report['opening_balance']['balance'], 2) }} @drCr($report['opening_balance']['type'])</td>
                            <td class="ledger-actions-col"></td>
                        </tr>
                        @endif
                        @forelse($entries as $entry)
                        <tr>
                            <td class="ledger-date-col">@istDate($entry->transaction_date)</td>
                            <td class="fw-semibold ledger-voucher-col">
                                @if($entry->voucher)
                                    <a href="{{ route('admin.vouchers.show', $entry->voucher->id) }}" class="report-detail-link" title="View voucher">
                                        {{ $entry->voucher->voucher_number }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $entry->particulars ?? ($entry->narration ?? $entry->description ?? '-') }}</td>
                            <td>
                                @if($entry->party_id && $entry->party)
                                    <a href="{{ route('admin.parties.show', $entry->party_id) }}" class="report-detail-link" title="View party history">
                                        {{ $entry->party->name }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end fw-semibold text-primary ledger-amount-col">{{ $entry->debit > 0 ? '₹' . number_format($entry->debit, 2) : '-' }}</td>
                            <td class="text-end fw-semibold text-danger ledger-amount-col">{{ $entry->credit > 0 ? '₹' . number_format($entry->credit, 2) : '-' }}</td>
                            <td class="text-end fw-bold ledger-amount-col">₹{{ number_format(abs($entry->running_balance), 2) }} @drCr($entry->balance_type)</td>
                            <td class="text-center ledger-actions-col">
                                <div class="btn-group btn-group-sm">
                                    @if($entry->voucher)
                                        <a href="{{ route('admin.vouchers.show', $entry->voucher->id) }}" class="btn btn-outline-primary" title="View Voucher">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('admin.ledgers.history', ['ledger' => $entry->id]) }}" class="btn btn-outline-secondary" title="View Ledger Party History">
                                        <i class="bi bi-card-list"></i>
                                    </a>
                                    @permission('audit-logs.view')
                                        <a href="{{ route('admin.audit-logs.index') }}?module=ledger&record_id={{ $entry->id }}" class="btn btn-outline-info" title="View Ledger Audit">
                                            <i class="bi bi-clock-history"></i>
                                        </a>
                                    @endpermission
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="8" class="text-muted text-center py-3">No entries found</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4">Total</td>
                            <td class="text-end fw-bold ledger-amount-col">₹{{ number_format($report['total_debit'], 2) }}</td>
                            <td class="text-end fw-bold ledger-amount-col">₹{{ number_format($report['total_credit'], 2) }}</td>
                            <td class="text-end fw-bold ledger-amount-col">₹{{ number_format($report['closing_balance']['balance'], 2) }} @drCr($report['closing_balance']['type'])</td>
                            <td class="ledger-actions-col"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @if($entries->hasPages())
                <div class="report-pagination">
                    {{ $entries->onEachSide(1)->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>
@endif
</div>
@endsection
