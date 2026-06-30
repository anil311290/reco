@extends('layouts.app')

@php
    $voucherLabels = [
        'income' => 'Sales',
        'expense' => 'Purchase',
        'payment' => 'Payment',
        'receipt' => 'Receipt',
        'journal' => 'Adjustment',
    ];
    $voucherLabel = $voucherLabels[$voucher->voucher_type] ?? ucfirst($voucher->voucher_type);
@endphp

@section('title', $voucherLabel . ' Voucher')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">{{ $voucherLabel }} Voucher</h4>
        <small class="text-muted">{{ $voucher->voucher_number }}</small>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.vouchers.type', $voucher->voucher_type) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Vouchers
        </a>
        @if($voucher->status === 'draft')
        <a href="{{ route('admin.vouchers.edit', $voucher->id) }}" class="btn btn-primary ms-2">
            <i class="bi bi-pencil me-2"></i>Edit Voucher
        </a>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Voucher Details</h5>
                <div class="mb-3">
                    <div class="text-muted small">Voucher Number</div>
                    <div>{{ $voucher->voucher_number }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Type</div>
                    <div>{{ $voucherLabel }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Date</div>
                    <div>{{ optional($voucher->voucher_date)->format('d/m/Y') }}</div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Status</div>
                    <div><span class="badge {{ $voucher->status_badge_class }}">{{ ucfirst($voucher->status) }}</span></div>
                </div>
                <div class="mb-3">
                    <div class="text-muted small">Party</div>
                    <div>{{ $voucher->party?->name ?? '-' }}</div>
                </div>
                <div class="mb-0">
                    <div class="text-muted small">Narration</div>
                    <div>{{ $voucher->narration ?: '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Voucher Lines</h5>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($voucher->lines as $line)
                            <tr>
                                <td>{{ $line->account?->account_name ?? '-' }}</td>
                                <td class="text-end">{{ number_format((float) $line->debit, 2) }}</td>
                                <td class="text-end">{{ number_format((float) $line->credit, 2) }}</td>
                                <td>{{ $line->description ?: '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <th class="text-end">Totals</th>
                                <th class="text-end">{{ number_format((float) $voucher->total_debit, 2) }}</th>
                                <th class="text-end">{{ number_format((float) $voucher->total_credit, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Remarks</h5>
                <p class="mb-0">{{ $voucher->remarks ?: '-' }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
