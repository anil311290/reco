@extends('layouts.app')

@section('title', 'AR Aging')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-people"></i> Receivables Watch</span>
                <h1 class="report-title">AR Aging</h1>
                <p class="report-subtitle">Keep receivables visible with a clearer debtor list, sharper balance hierarchy, and quick summary metrics.</p>
            </div>
            <div class="col-lg-4">
                <div class="report-toolbar">
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Reports</a>
                    <a href="{{ route('admin.export.excel', ['type' => 'debtors']) }}" class="btn btn-outline-success report-btn-export">
                        <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                    </a>
                    <a href="{{ route('admin.export.debtors-outstanding.pdf') }}" class="btn btn-outline-danger report-btn-export">
                        <i class="bi bi-file-earmark-pdf"></i>PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-stats-grid">
        <div class="report-stat report-stat--danger">
            <p class="report-stat-label">Total Outstanding</p>
            <h3 class="report-stat-value">₹{{ number_format($report['total'], 2) }}</h3>
            <p class="report-stat-note">Open receivables from all debtors.</p>
        </div>
        <div class="report-stat report-stat--info">
            <p class="report-stat-label">Total Debtors</p>
            <h3 class="report-stat-value">{{ count($report['debtors']) }}</h3>
            <p class="report-stat-note">Customers with outstanding balances.</p>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-person-lines-fill text-danger"></i>Outstanding Debtors</h6>
            <span class="report-pill report-pill--danger">₹{{ number_format($report['total'], 2) }}</span>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Party Name</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th class="text-end">Debit (₹)</th>
                        <th class="text-end">Credit (₹)</th>
                        <th class="text-end">Balance (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['debtors'] as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="fw-semibold">{{ $item['party']->name }}</td>
                        <td>{{ $item['party']->mobile ?? '-' }}</td>
                        <td>{{ $item['party']->email ?? '-' }}</td>
                        <td class="text-end fw-semibold">₹{{ number_format($item['debit'], 2) }}</td>
                        <td class="text-end fw-semibold">₹{{ number_format($item['credit'], 2) }}</td>
                        <td class="text-end fw-bold text-danger">₹{{ number_format($item['balance'], 2) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-muted text-center py-4">No outstanding debtors found</td></tr>
                    @endforelse
                </tbody>
                @if(count($report['debtors']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="6">Total Outstanding</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['total'], 2) }}</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
