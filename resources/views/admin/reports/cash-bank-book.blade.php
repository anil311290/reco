@extends('layouts.app')

@section('title', ($mode === 'cash' ? 'Cash Book' : 'Bank Book'))

@include('admin.reports._theme')

@section('content')
@php
    $routeName = $mode === 'cash' ? 'admin.reports.cash-book' : 'admin.reports.bank-book';
    $title = $book['title'] ?? ($mode === 'cash' ? 'Cash Book' : 'Bank Book');
    $report = $book['report'] ?? null;
@endphp
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi {{ $mode === 'cash' ? 'bi-cash-coin' : 'bi-bank' }}"></i> Books of Accounts</span>
                <h1 class="report-title">{{ $title }}</h1>
                <p class="report-subtitle">
                    {{ $mode === 'cash'
                        ? 'Cash ledger receipts and payments with opening and closing balance (Tally Cash Book style).'
                        : 'Bank / OD ledger statements for the selected account and period.' }}
                </p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="{{ route($routeName) }}" class="row g-3 align-items-end">
            <div class="col-lg-3 col-md-6">
                <label class="form-label">{{ $mode === 'cash' ? 'Cash Account' : 'Bank Account' }}</label>
                <select name="account_id" class="form-select" @if(($book['accounts'] ?? collect())->isEmpty()) disabled @endif>
                    @forelse($book['accounts'] as $account)
                        <option value="{{ $account->id }}" {{ (int) $accountId === (int) $account->id ? 'selected' : '' }}>
                            {{ $account->account_code }} - {{ $account->account_name }}
                        </option>
                    @empty
                        <option value="">No accounts found</option>
                    @endforelse
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
            <div class="col-lg-4 report-filter-actions">
                <button type="submit" class="btn btn-primary px-4" @if(($book['accounts'] ?? collect())->isEmpty()) disabled @endif>
                    <i class="bi bi-funnel me-1"></i>Filter
                </button>
                @if(!empty($accountId) && $report)
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

    @if(!empty($book['message']))
        <div class="report-panel">
            <div class="report-empty">
                <div class="report-empty-icon"><i class="bi bi-exclamation-circle"></i></div>
                <div>
                    <h5 class="mb-2">{{ $title }} unavailable</h5>
                    <p class="mb-0">{{ $book['message'] }}</p>
                </div>
            </div>
        </div>
    @elseif($report)
        <div class="report-stats-grid">
            <div class="report-stat report-stat--info">
                <p class="report-stat-label">Account</p>
                <h3 class="report-stat-value">{{ $report['account']->account_code }}</h3>
                <p class="report-stat-note">{{ $report['account']->account_name }}</p>
            </div>
            <div class="report-stat report-stat--primary">
                <p class="report-stat-label">Opening</p>
                <h3 class="report-stat-value">₹{{ number_format($report['opening_balance']['balance'], 2) }}</h3>
                <p class="report-stat-note">{{ strtoupper($report['opening_balance']['type']) }}</p>
            </div>
            <div class="report-stat report-stat--success">
                <p class="report-stat-label">Receipts (Dr)</p>
                <h3 class="report-stat-value">₹{{ number_format($report['total_debit'], 2) }}</h3>
                <p class="report-stat-note">Money in</p>
            </div>
            <div class="report-stat report-stat--danger">
                <p class="report-stat-label">Payments (Cr)</p>
                <h3 class="report-stat-value">₹{{ number_format($report['total_credit'], 2) }}</h3>
                <p class="report-stat-note">Money out</p>
            </div>
            <div class="report-stat report-stat--warning">
                <p class="report-stat-label">Closing</p>
                <h3 class="report-stat-value">₹{{ number_format($report['closing_balance']['balance'], 2) }}</h3>
                <p class="report-stat-note">{{ strtoupper($report['closing_balance']['type']) }}</p>
            </div>
        </div>

        <div class="report-panel">
            <div class="report-panel-header">
                <h6 class="report-panel-title"><i class="bi bi-list-ul text-primary"></i>{{ $title }} Entries</h6>
            </div>
            <div class="report-panel-body report-panel-body--flush">
                <table class="table report-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Voucher #</th>
                            <th>Particulars</th>
                            <th class="text-end">Receipts (₹)</th>
                            <th class="text-end">Payments (₹)</th>
                            <th class="text-end">Balance (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="report-row-emphasis">
                            <td colspan="3" class="fw-semibold">Opening Balance</td>
                            <td class="text-end">-</td>
                            <td class="text-end">-</td>
                            <td class="text-end fw-bold">
                                ₹{{ number_format($report['opening_balance']['balance'], 2) }}
                                {{ strtoupper($report['opening_balance']['type']) }}
                            </td>
                        </tr>
                        @forelse($report['entries'] as $entry)
                        <tr>
                            <td>@istDate($entry->transaction_date)</td>
                            <td class="fw-semibold">{{ $entry->voucher->voucher_number ?? '-' }}</td>
                            <td>{{ $entry->description ?: ($entry->voucher->narration ?? '-') }}</td>
                            <td class="text-end">{{ $entry->debit > 0 ? '₹' . number_format($entry->debit, 2) : '-' }}</td>
                            <td class="text-end">{{ $entry->credit > 0 ? '₹' . number_format($entry->credit, 2) : '-' }}</td>
                            <td class="text-end fw-semibold">
                                ₹{{ number_format($entry->running_balance, 2) }}
                                {{ strtoupper($entry->balance_type) }}
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-muted text-center py-4">No entries in this period</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3">Closing Balance</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['total_debit'], 2) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['total_credit'], 2) }}</td>
                            <td class="text-end fw-bold">
                                ₹{{ number_format($report['closing_balance']['balance'], 2) }}
                                {{ strtoupper($report['closing_balance']['type']) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
