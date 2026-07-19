@extends('layouts.app')

@section('title', 'Payables Outstanding')

@include('admin.reports._theme')

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
                    <a href="{{ route('admin.reports.index') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to Reports</a>
                    <a href="{{ route('admin.export.excel', ['type' => 'creditors']) }}" class="btn btn-outline-success report-btn-export">
                        <i class="bi bi-file-earmark-spreadsheet"></i>Excel
                    </a>
                    <a href="{{ route('admin.export.creditors-outstanding.pdf') }}" class="btn btn-outline-danger report-btn-export">
                        <i class="bi bi-file-earmark-pdf"></i>PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="report-stats-grid">
        <div class="report-stat report-stat--warning">
            <p class="report-stat-label">Total Outstanding</p>
            <h3 class="report-stat-value">₹{{ number_format($report['total'], 2) }}</h3>
            <p class="report-stat-note">Open payables across all creditors.</p>
        </div>
        <div class="report-stat report-stat--info">
            <p class="report-stat-label">Creditors Count</p>
            <h3 class="report-stat-value">{{ count($report['creditors']) }}</h3>
            <p class="report-stat-note">Suppliers with outstanding balances.</p>
        </div>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <h6 class="report-panel-title"><i class="bi bi-person-lines-fill text-warning"></i>Outstanding Creditors</h6>
            <span class="report-pill report-pill--warning">₹{{ number_format($report['total'], 2) }}</span>
        </div>
        <div class="report-panel-body report-panel-body--flush">
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Party</th>
                        <th>Mobile</th>
                        <th>Email</th>
                        <th class="text-end">Balance (₹) Cr</th>
                        <th class="text-center">History</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['creditors'] as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td class="fw-semibold">
                            @permission('parties.view')
                                <a href="{{ route('admin.parties.show', $item['party']->id) }}" class="report-detail-link" title="View party history">
                                    {{ $item['party']->name }}
                                </a>
                            @else
                                {{ $item['party']->name }}
                            @endpermission
                        </td>
                        <td>{{ $item['party']->mobile ?? '-' }}</td>
                        <td>{{ $item['party']->email ?? '-' }}</td>
                        <td class="text-end fw-bold text-warning">₹{{ number_format($item['balance'], 2) }}</td>
                        <td class="text-center">
                            <a href="{{ route('admin.parties.show', $item['party']->id) }}" class="btn btn-sm btn-outline-primary" title="View party history">History</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-muted text-center py-4">No outstanding payables</td></tr>
                    @endforelse
                </tbody>
                @if(count($report['creditors']) > 0)
                <tfoot>
                    <tr>
                        <td colspan="4">Total Outstanding</td>
                        <td class="text-end fw-bold">₹{{ number_format($report['total'], 2) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
