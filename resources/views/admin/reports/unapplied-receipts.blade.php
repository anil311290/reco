@extends('layouts.app')

@section('title', 'Unapplied Payments & Receipts')

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

    .unapplied-cash-tabs {
        margin-bottom: 1rem;
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
                <h1 class="report-title">Unapplied Payments &amp; Receipts</h1>
                <p class="report-subtitle">Review unapplied receipts and payments and apply them to an open bill.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('admin.reports.debtors-outstanding') }}" class="btn report-btn-soft"><i class="bi bi-arrow-left me-1"></i>Back to AP / AR Reports</a>
            </div>
        </div>
    </div>

    <div class="report-filter-card mb-4">
        <form method="GET" action="{{ route('admin.reports.unapplied-receipts') }}" class="row g-3 align-items-end">
            <div class="col-12 col-md-4">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" id="from_date" name="from_date" class="form-control" value="{{ $fromDate }}" required>
            </div>
            <div class="col-12 col-md-4">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" id="to_date" name="to_date" class="form-control" value="{{ $toDate }}" required>
            </div>
            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
        </form>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <h5 class="mb-1">Unapplied Payments &amp; Receipts</h5>
                <p class="mb-0 text-muted">Posted receipts and payments with a remaining unapplied amount.</p>
            </div>
        </div>
        <ul class="nav nav-tabs report-tabs unapplied-cash-tabs px-3 pt-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#unapplied-receipts-pane" type="button" role="tab">
                    <i class="bi bi-arrow-down-left-circle me-1"></i>Unapplied Receipts
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis ms-1">{{ count($receipts) }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#unapplied-payments-pane" type="button" role="tab">
                    <i class="bi bi-arrow-up-right-circle me-1"></i>Unapplied Payments
                    <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis ms-1">{{ count($payments) }}</span>
                </button>
            </li>
        </ul>
        <div class="tab-content">
            @foreach(['receipts' => $receipts, 'payments' => $payments] as $tabKey => $tabItems)
            <div class="tab-pane fade {{ $tabKey === 'receipts' ? 'show active' : '' }}" id="unapplied-{{ $tabKey }}-pane" role="tabpanel">
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
                            @forelse($tabItems as $item)
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
                            <span class="badge {{ $item['reference_number'] === 'Opening Balance' ? 'bg-info-subtle text-info-emphasis' : ($item['voucher_type'] === 'receipt' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis') }}">
                                {{ $item['reference_number'] === 'Opening Balance' ? 'Opening Balance' : ucfirst($item['voucher_type']) }}
                            </span>
                        </td>
                        <td class="text-end">{{ number_format($item['voucher_amount'], 2) }}</td>
                        <td class="text-end fw-bold text-success">{{ number_format($item['unapplied_amount'], 2) }}</td>
                        <td>
                            @if($item['invoices']->isEmpty())
                                <span class="text-muted">No open bills for this party</span>
                            @else
                                <form class="unapplied-allocation-form d-flex gap-2 align-items-center"
                                      action="{{ route('admin.parties.apply-unapplied', $item['party']->id) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="source" value="{{ $item['allocation_source'] ?? 'voucher' }}">
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
                                <td colspan="7" class="text-center text-muted py-5">No unapplied {{ $tabKey }} found for this date range.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endforeach
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
