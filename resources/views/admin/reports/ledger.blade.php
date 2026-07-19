@extends('layouts.app')

@section('title', 'Account Ledger')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-book"></i> Transaction Trail</span>
                <h1 class="report-title">Ledger</h1>
                <p class="report-subtitle">Select one ledger to view opening balance, voucher-wise movement, and closing balance (Tally ledger style).</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
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
                    <a href="{{ route('admin.export.excel', ['type' => 'ledger', 'account_id' => $accountId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-outline-success report-btn-export">
                        <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                    </a>
                    <a href="{{ route('admin.export.ledger.pdf', ['account_id' => $accountId, 'date_from' => $dateFrom, 'date_to' => $dateTo]) }}" class="btn btn-outline-danger report-btn-export">
                        <i class="bi bi-file-earmark-pdf"></i>PDF
                    </a>
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
            <p class="report-stat-note">{{ strtoupper($report['opening_balance']['type']) }}</p>
        </div>
        <div class="report-stat {{ $report['closing_balance']['type'] === 'debit' ? 'report-stat--success' : 'report-stat--warning' }}">
            <p class="report-stat-label">Closing Balance</p>
            <h3 class="report-stat-value">₹{{ number_format($report['closing_balance']['balance'], 2) }}</h3>
            <p class="report-stat-note">{{ strtoupper($report['closing_balance']['type']) }}</p>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-list-columns-reverse text-primary"></i>Ledger Entries</h6>
            <span class="report-pill report-pill--info">Total Debit ₹{{ number_format($report['total_debit'], 2) }} | Total Credit ₹{{ number_format($report['total_credit'], 2) }}</span>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Voucher #</th>
                        <th>Particulars</th>
                        <th>Party</th>
                        <th class="text-end">Debit (₹)</th>
                        <th class="text-end">Credit (₹)</th>
                        <th class="text-end">Balance (₹)</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="report-row-emphasis">
                        <td colspan="4" class="fw-semibold">Opening Balance</td>
                        <td class="text-end">-</td>
                        <td class="text-end">-</td>
                        <td class="text-end fw-bold">
                            ₹{{ number_format($report['opening_balance']['balance'], 2) }} {{ strtoupper($report['opening_balance']['type']) }}
                        </td>
                    </tr>
                    @forelse($report['entries'] as $entry)
                    <tr>
                        <td>@istDate($entry->transaction_date)</td>
                        <td class="fw-semibold">
                            @if($entry->voucher)
                                <a href="{{ route('admin.vouchers.show', $entry->voucher->id) }}" class="report-detail-link" title="View voucher">
                                    {{ $entry->voucher->voucher_number }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $entry->narration ?? $entry->description ?? '-' }}</td>
                        <td>
                            @if($entry->party_id && $entry->party)
                                <a href="{{ route('admin.parties.show', $entry->party_id) }}" class="report-detail-link" title="View party history">
                                    {{ $entry->party->name }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td class="text-end fw-semibold text-primary">{{ $entry->debit > 0 ? '₹' . number_format($entry->debit, 2) : '-' }}</td>
                        <td class="text-end fw-semibold text-danger">{{ $entry->credit > 0 ? '₹' . number_format($entry->credit, 2) : '-' }}</td>
                        <td class="text-end fw-bold">₹{{ number_format(abs($entry->running_balance), 2) }} {{ strtoupper($entry->balance_type) }}</td>
                        <td class="text-center">
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
                        <td class="text-end fw-bold">₹{{ number_format($report['total_debit'], 2) }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['total_credit'], 2) }}</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['closing_balance']['balance'], 2) }} {{ strtoupper($report['closing_balance']['type']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@endif
</div>
@endsection
