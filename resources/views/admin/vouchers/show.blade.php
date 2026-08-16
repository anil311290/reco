@extends('layouts.app')

@php
    $voucherLabels = [
        'income' => 'Sales',
        'expense' => 'Purchase',
        'payment' => 'Payment',
        'receipt' => 'Receipt',
        'journal' => 'Adjustment',
        'adjustment' => 'Adjustment',
    ];
    $voucherLabel = $voucherLabels[$voucher->voucher_type] ?? ucfirst($voucher->voucher_type);
    $isStandaloneBookVoucher = in_array($voucher->voucher_type, ['payment', 'receipt', 'journal', 'adjustment'], true)
        && !$voucher->sales_invoice_id
        && !$voucher->purchase_invoice_id;
    $canEditVoucher = $voucher->status === 'draft'
        || ($voucher->status === 'posted' && $isStandaloneBookVoucher);
@endphp

@section('title', $voucherLabel . ' Voucher')

@section('content')
<style>
    .detail-panel .label {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 0.15rem;
    }
    .detail-panel .value {
        font-size: 0.9375rem;
        color: #212529;
        font-weight: 500;
    }
    .detail-panel .detail-row {
        margin-bottom: 1rem;
    }
    .detail-panel .detail-row:last-child {
        margin-bottom: 0;
    }
    .voucher-lines-table {
        --bs-table-bg: transparent;
        margin-bottom: 0;
        font-size: 0.875rem;
    }
    .voucher-lines-table thead th {
        background: #f8f9fa;
        color: #495057;
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        white-space: nowrap;
        border-bottom-width: 1px;
        padding: 0.65rem 0.75rem;
    }
    .voucher-lines-table tbody td,
    .voucher-lines-table tfoot td {
        padding: 0.65rem 0.75rem;
        vertical-align: middle;
    }
    .voucher-lines-table tfoot tr {
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
    }
    .voucher-lines-table .tabular-nums {
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
</style>

<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">{{ $voucherLabel }} Voucher</h4>
        <small class="text-muted">{{ $voucher->voucher_number }}</small>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.vouchers.type', $voucher->voucher_type) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Vouchers
        </a>
        @if($canEditVoucher)
        <a href="{{ route('admin.vouchers.edit', $voucher->id) }}" class="btn btn-primary ms-2">
            <i class="bi bi-pencil me-2"></i>Edit Voucher
        </a>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body detail-panel">
                <h5 class="card-title mb-3">Voucher Details</h5>
                <div class="detail-row">
                    <div class="label">Voucher Number</div>
                    <div class="value">{{ $voucher->voucher_number }}</div>
                </div>
                <div class="detail-row">
                    <div class="label">Type</div>
                    <div class="value">{{ $voucherLabel }}</div>
                </div>
                <div class="detail-row">
                    <div class="label">Date</div>
                    <div class="value">@istDate($voucher->voucher_date)</div>
                </div>
                <div class="detail-row">
                    <div class="label">Status</div>
                    <div class="value">
                        <span class="badge {{ $voucher->status_badge_class }}">{{ ucfirst($voucher->status) }}</span>
                    </div>
                </div>
                @if($voucher->party)
                <div class="detail-row">
                    <div class="label">Party</div>
                    <div class="value">
                        <a href="{{ route('admin.parties.show', $voucher->party->id) }}" class="report-detail-link">
                            {{ $voucher->party->name }}
                        </a>
                    </div>
                </div>
                @endif
                <div class="detail-row">
                    <div class="label">Amount</div>
                    <div class="value">&#8377; {{ number_format((float) $voucher->total_debit, 2) }}</div>
                </div>
                @if($voucher->financialYear)
                <div class="detail-row">
                    <div class="label">Financial Year</div>
                    <div class="value">{{ $voucher->financialYear->name }}</div>
                </div>
                @endif
                <div class="detail-row">
                    <div class="label">Narration</div>
                    <div class="value fw-normal">{{ $voucher->narration ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Voucher Lines</h5>
                <div class="table-responsive">
                    <table class="table table-hover voucher-lines-table">
                        <thead>
                            <tr>
                                <th>Particulars</th>
                                <th class="text-end">Debit (&#8377;)</th>
                                <th class="text-end">Credit (&#8377;)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($voucher->lines as $line)
                            <tr>
                                <td>
                                    <div class="fw-medium">{{ $line->account?->account_name ?? '—' }}</div>
                                    @if($line->party)
                                    <small class="text-muted">
                                        <a href="{{ route('admin.parties.show', $line->party->id) }}" class="report-detail-link">
                                            {{ $line->party->name }}
                                        </a>
                                    </small>
                                    @endif
                                </td>
                                <td class="text-end tabular-nums">
                                    {{ $line->debit > 0 ? number_format((float) $line->debit, 2) : '—' }}
                                </td>
                                <td class="text-end tabular-nums">
                                    {{ $line->credit > 0 ? number_format((float) $line->credit, 2) : '—' }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted py-4">No voucher lines found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($voucher->lines->count() > 0)
                        <tfoot>
                            <tr class="fw-semibold">
                                <td class="text-end text-muted">Total</td>
                                <td class="text-end tabular-nums">{{ number_format((float) $voucher->total_debit, 2) }}</td>
                                <td class="text-end tabular-nums">{{ number_format((float) $voucher->total_credit, 2) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@if(in_array($voucher->voucher_type, ['receipt', 'payment'], true))
    @php $mappedInvoices = $voucher->getMappedInvoices(); @endphp
    @if($mappedInvoices->isNotEmpty())
    <div class="row g-4 mt-1">
        <div class="col-12">
            <div class="card">
                <div class="card-header"><h6 class="mb-0"><i class="bi bi-link-45deg me-1"></i>Invoices Settled by this {{ $voucherLabel }}</h6></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0 voucher-lines-table">
                            <thead>
                                <tr>
                                    <th>Invoice No</th>
                                    <th>Type</th>
                                    <th class="text-end">Allocated (&#8377;)</th>
                                    <th class="text-end">Settled (&#8377;)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mappedInvoices as $mapping)
                                @php $mappedInvoice = $mapping->getInvoice(); @endphp
                                <tr>
                                    <td>
                                        @if($mappedInvoice && $mapping->invoice_type === 'sales')
                                        <a href="{{ route('admin.sales-invoices.show', $mappedInvoice->id) }}">{{ $mappedInvoice->invoice_number }}</a>
                                        @elseif($mappedInvoice)
                                        <a href="{{ route('admin.purchase-invoices.show', $mappedInvoice->id) }}">{{ $mappedInvoice->invoice_number }}</a>
                                        @else
                                        N/A
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($mapping->invoice_type) }}</td>
                                    <td class="text-end tabular-nums">₹{{ number_format((float) $mapping->amount_allocated, 2) }}</td>
                                    <td class="text-end tabular-nums">₹{{ number_format((float) $mapping->amount_settled, 2) }}</td>
                                    <td>
                                        @php $mappingColors = ['pending'=>'secondary','partial'=>'warning','full'=>'success','reversed'=>'dark']; @endphp
                                        <span class="badge bg-{{ $mappingColors[$mapping->status] ?? 'secondary' }}">{{ ucfirst($mapping->status) }}</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endif
@endsection
