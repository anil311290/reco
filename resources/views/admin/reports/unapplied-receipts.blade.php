@extends('layouts.app')

@section('title', 'Unapplied Receipts & Payments')

@include('admin.reports._theme')

@push('styles')
<style>
    .unapplied-report-table {
        min-width: 980px;
    }

    .unapplied-allocation-form {
        min-width: 360px;
    }

    .unapplied-allocation-form select {
        min-width: 215px;
    }

    .unapplied-amount-input {
        width: 108px;
        flex: 0 0 108px;
    }

    @media (max-width: 767.98px) {
        .unapplied-report-table {
            min-width: 900px;
        }

        .unapplied-allocation-form {
            min-width: 320px;
        }
    }
</style>
@endpush

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-arrow-repeat"></i> AP / AR Reports</span>
                <h1 class="report-title">Unapplied Receipts &amp; Payments</h1>
                <p class="report-subtitle">Apply an unapplied receipt or payment to any open bill belonging to the same party.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('admin.reports.debtors-outstanding') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to AP / AR Reports</a>
            </div>
        </div>
    </div>

    <div class="report-filter-card mb-4">
        <form method="GET" action="{{ route('admin.reports.unapplied-receipts') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="as_of_date" class="form-label">As of Date</label>
                <input type="date" id="as_of_date" name="as_of_date" class="form-control" value="{{ $asOfDate }}">
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
        </form>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <h5 class="mb-1">Unapplied Receipts &amp; Payments</h5>
                <p class="mb-0 text-muted">Only posted vouchers with a remaining unapplied amount are listed.</p>
            </div>
            <span class="report-pill report-pill--info">{{ count($items) }} Items</span>
        </div>
        <div class="table-responsive">
            <table class="table report-table unapplied-report-table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Voucher</th>
                        <th>Date</th>
                        <th>Party</th>
                        <th>Type</th>
                        <th class="text-end">Voucher Amount</th>
                        <th class="text-end">Unapplied</th>
                        <th style="min-width: 380px;">Apply To Bill</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td class="fw-semibold">
                            <a href="{{ route('admin.vouchers.show', $item['voucher_id']) }}" class="report-detail-link">
                                {{ $item['voucher_number'] }}
                            </a>
                            @if($item['reference_number'])
                                <small class="d-block text-muted">{{ $item['reference_number'] }}</small>
                            @endif
                        </td>
                        <td>{{ $item['voucher_date'] }}</td>
                        <td>
                            <div class="fw-semibold">{{ $item['party']->name }}</div>
                            <small class="text-muted">{{ $item['party']->party_code }}</small>
                        </td>
                        <td>
                            <span class="badge {{ $item['voucher_type'] === 'receipt' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis' }}">
                                {{ ucfirst($item['voucher_type']) }}
                            </span>
                        </td>
                        <td class="text-end">{{ number_format($item['voucher_amount'], 2) }}</td>
                        <td class="text-end fw-bold text-success">{{ number_format($item['unapplied_amount'], 2) }}</td>
                        <td>
                            @if($item['invoices']->isEmpty())
                                <span class="text-muted">No open bills for this party</span>
                            @else
                                <form class="unapplied-allocation-form d-flex gap-2 align-items-center"
                                      action="{{ route('admin.parties.apply-unbilled', $item['party']->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="source" value="voucher">
                                    <input type="hidden" name="voucher_id" value="{{ $item['voucher_id'] }}">
                                    <select name="invoice_id" class="form-select form-select-sm" required>
                                        <option value="">Select bill</option>
                                        @foreach($item['invoices'] as $invoice)
                                            <option value="{{ $invoice->id }}" data-balance="{{ $invoice->balance_due }}">
                                                {{ $invoice->invoice_number }} - Balance: {{ number_format((float) $invoice->balance_due, 2) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="amount" class="form-control form-control-sm unapplied-amount-input"
                                           value="{{ number_format(min($item['unapplied_amount'], (float) $item['invoices']->first()->balance_due), 2, '.', '') }}"
                                           min="0.01" max="{{ $item['unapplied_amount'] }}" step="0.01" required>
                                    <button type="submit" class="btn btn-sm btn-success text-nowrap">
                                        <i class="bi bi-check2-circle me-1"></i>Apply
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">No unapplied receipts or payments found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $(document).on('change', '.unapplied-allocation-form select[name="invoice_id"]', function () {
            const balance = parseFloat($(this).find(':selected').data('balance')) || 0;
            const $form = $(this).closest('form');
            const available = parseFloat($form.find('input[name="amount"]').attr('max')) || 0;
            if (balance > 0) {
                $form.find('input[name="amount"]').val(Math.min(balance, available).toFixed(2));
            }
        });

        $(document).on('submit', '.unapplied-allocation-form', function (event) {
            event.preventDefault();
            const $form = $(this);
            const $button = $form.find('button[type="submit"]');
            $button.prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: 'POST',
                data: $form.serialize(),
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                success: function (response) {
                    toastr.success(response.message || 'Amount applied successfully.');
                    window.location.reload();
                },
                error: function (xhr) {
                    $button.prop('disabled', false);
                    toastr.error(xhr.responseJSON?.message || 'Unable to apply amount.');
                }
            });
        });
    });
</script>
@endpush
