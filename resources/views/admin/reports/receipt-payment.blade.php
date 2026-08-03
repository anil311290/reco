@extends('layouts.app')

@section('title', 'Receipt & Payment')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-cash-coin"></i> Cash &amp; Bank Summary</span>
                <h1 class="report-title">Receipt &amp; Payment</h1>
                <p class="report-subtitle">Every cash, bank, and OD movement of the period grouped head-wise, with opening and closing balances.</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Reports</a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <form method="GET" action="{{ route('admin.reports.receipt-payment') }}" class="row g-3 align-items-end">
            <div class="col-lg-4 col-md-6">
                <label class="form-label">Financial Year</label>
                <select name="financial_year_id" class="form-select">
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" {{ ($financialYearId ?? '') == $fy->id ? 'selected' : '' }}>
                            {{ $fy->name }} (@istDate($fy->start_date) to @istDate($fy->end_date))
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
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
                <a href="{{ route('admin.export.excel', ['type' => 'receipt-payment', 'date_from' => $dateFrom, 'date_to' => $dateTo, 'financial_year_id' => $financialYearId]) }}" class="btn btn-outline-success report-btn-export">
                    <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                </a>
                <a href="{{ route('admin.export.receipt-payment.pdf', ['date_from' => $dateFrom, 'date_to' => $dateTo, 'financial_year_id' => $financialYearId]) }}" class="btn btn-outline-danger report-btn-export">
                    <i class="bi bi-file-earmark-pdf"></i>PDF
                </a>
            </div>
        </form>
    </div>

    @if($report['message'])
        <div class="report-panel">
            <div class="report-empty">
                <div class="report-empty-icon"><i class="bi bi-exclamation-circle"></i></div>
                <div>
                    <h5 class="mb-2">Receipt &amp; Payment unavailable</h5>
                    <p class="mb-0">{{ $report['message'] }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="report-stats-grid">
            <div class="report-stat report-stat--primary">
                <p class="report-stat-label">Opening Balance</p>
                <h3 class="report-stat-value">₹{{ number_format($report['opening_total'], 2) }}</h3>
                <p class="report-stat-note">Cash, bank, and OD as on @istDate($report['date_from'])</p>
            </div>
            <div class="report-stat report-stat--success">
                <p class="report-stat-label">Total Receipts</p>
                <h3 class="report-stat-value">₹{{ number_format($report['receipts']['total'], 2) }}</h3>
                <p class="report-stat-note">Money received during the period.</p>
            </div>
            <div class="report-stat report-stat--danger">
                <p class="report-stat-label">Total Payments</p>
                <h3 class="report-stat-value">₹{{ number_format($report['payments']['total'], 2) }}</h3>
                <p class="report-stat-note">Money paid during the period.</p>
            </div>
            <div class="report-stat report-stat--warning">
                <p class="report-stat-label">Closing Balance</p>
                <h3 class="report-stat-value">₹{{ number_format($report['closing_total'], 2) }}</h3>
                <p class="report-stat-note">Cash, bank, and OD as on @istDate($report['date_to'])</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-xl-6">
                <div class="report-panel h-100">
                    <div class="report-panel-header">
                        <h6 class="report-panel-title"><i class="bi bi-arrow-down-circle text-success"></i>Receipts</h6>
                        <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
                        <span class="report-pill report-pill--success">₹{{ number_format($report['receipts_side_total'], 2) }}</span>
                    </div>
                    <div class="report-panel-body report-panel-body--flush">
                        <table class="table report-table table-hover mb-0">
                            <tbody>
                                @if(abs((float) $report['opening_total']) >= 0.01)
                                <tr class="report-row-emphasis">
                                    <td class="fw-semibold">Opening Balance b/f</td>
                                    <td class="text-end fw-bold">₹{{ number_format($report['opening_total'], 2) }}</td>
                                </tr>
                                @endif
                                @forelse($report['receipts']['rows'] as $row)
                                <tr>
                                    <td>
                                        @if($row['account'])
                                            <a href="{{ route('admin.reports.ledger', ['account_id' => $row['account']->id]) }}" class="report-detail-link" title="View ledger">
                                                {{ $row['label'] }}
                                            </a>
                                        @else
                                            {{ $row['label'] }}
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold text-success">₹{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-muted text-center py-3">No receipts in this period</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>Total</td>
                                    <td class="text-end fw-bold text-success">₹{{ number_format($report['receipts_side_total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-xl-6">
                <div class="report-panel h-100">
                    <div class="report-panel-header">
                        <h6 class="report-panel-title"><i class="bi bi-arrow-up-circle text-danger"></i>Payments</h6>
                        <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
                        <span class="report-pill report-pill--danger">₹{{ number_format($report['payments_side_total'], 2) }}</span>
                    </div>
                    <div class="report-panel-body report-panel-body--flush">
                        <table class="table report-table table-hover mb-0">
                            <tbody>
                                @forelse($report['payments']['rows'] as $row)
                                <tr>
                                    <td>
                                        @if($row['account'])
                                            <a href="{{ route('admin.reports.ledger', ['account_id' => $row['account']->id]) }}" class="report-detail-link" title="View ledger">
                                                {{ $row['label'] }}
                                            </a>
                                        @else
                                            {{ $row['label'] }}
                                        @endif
                                    </td>
                                    <td class="text-end fw-semibold text-danger">₹{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="2" class="text-muted text-center py-3">No payments in this period</td></tr>
                                @endforelse
                                <tr class="report-row-emphasis">
                                    <td class="fw-semibold">Closing Balance c/f</td>
                                    <td class="text-end fw-bold">₹{{ number_format($report['closing_total'], 2) }}</td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td>Total</td>
                                    <td class="text-end fw-bold text-danger">₹{{ number_format($report['payments_side_total'], 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="report-panel">
            <div class="report-panel-header">
                <h6 class="report-panel-title"><i class="bi bi-wallet2 text-primary"></i>Cash / Bank Ledgers</h6>
                <span class="report-pill report-pill--info">@istDate($dateFrom) to @istDate($dateTo)</span>
                <span class="report-pill {{ $report['is_balanced'] ? 'report-pill--success' : 'report-pill--danger' }}">
                    <i class="bi {{ $report['is_balanced'] ? 'bi-check-circle' : 'bi-exclamation-circle' }}"></i>
                    {{ $report['is_balanced'] ? 'Balanced' : 'Not balanced' }}
                </span>
            </div>
            <div class="report-panel-body report-panel-body--flush">
                <table class="table report-table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Ledger</th>
                            <th class="text-end">Opening (₹)</th>
                            <th class="text-end">Received (₹)</th>
                            <th class="text-end">Paid (₹)</th>
                            <th class="text-end">Closing (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($report['accounts'] as $row)
                        <tr>
                            <td>{{ $row['account']->account_code }}</td>
                            <td>
                                <a href="{{ route('admin.reports.ledger', ['account_id' => $row['account']->id, 'date_from' => $report['date_from'], 'date_to' => $report['date_to']]) }}" class="report-detail-link" title="View ledger">
                                    {{ $row['account']->account_name }}
                                </a>
                            </td>
                            <td class="text-end">₹{{ number_format($row['opening'], 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($row['received'], 2) }}</td>
                            <td class="text-end text-danger">₹{{ number_format($row['paid'], 2) }}</td>
                            <td class="text-end fw-semibold">₹{{ number_format($row['closing'], 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Total</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['opening_total'], 2) }}</td>
                            <td class="text-end fw-bold text-success">₹{{ number_format(collect($report['accounts'])->sum('received'), 2) }}</td>
                            <td class="text-end fw-bold text-danger">₹{{ number_format(collect($report['accounts'])->sum('paid'), 2) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($report['closing_total'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
