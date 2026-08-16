@extends('layouts.app')

@section('title', 'Party Details')

@section('content')
<style>
    .party-history-table {
        --bs-table-bg: transparent;
        margin-bottom: 0;
        font-size: 0.875rem;
    }
    .party-history-table thead th {
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
    .party-history-table tbody td,
    .party-history-table tfoot td {
        padding: 0.65rem 0.75rem;
        vertical-align: middle;
        white-space: nowrap;
    }
    .party-history-table tbody td.particulars-cell {
        white-space: normal;
        max-width: 280px;
        color: #6c757d;
        font-size: 0.8125rem;
    }
    .party-history-table tfoot tr {
        background: #f8f9fa;
        border-top: 2px solid #dee2e6;
    }
    .party-history-table .tabular-nums {
        font-variant-numeric: tabular-nums;
    }
    .party-summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem 1.25rem;
    }
    .party-summary-grid .label {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 0.15rem;
    }
    .party-summary-grid .value {
        font-size: 0.9375rem;
        color: #212529;
        font-weight: 500;
    }
</style>

<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">{{ $party->name }}</h4>
        <small class="text-muted">{{ $party->party_code }} &middot; {{ ucfirst($party->type) }}</small>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.parties.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Parties
        </a>
        @if(in_array($party->type, ['debtor', 'creditor'], true) && $outstandingInvoices->isNotEmpty())
        <button type="button" class="btn btn-success ms-2" data-bs-toggle="modal" data-bs-target="#multiInvoicePaymentModal">
            <i class="bi bi-cash-stack me-2"></i>Record Payment
        </button>
        @endif
        @permission('parties.edit')
        <a href="{{ route('admin.parties.edit', $party->id) }}" class="btn btn-primary ms-2">
            <i class="bi bi-pencil me-2"></i>Edit Party
        </a>
        @endpermission
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <h5 class="card-title mb-3">Party Details</h5>
        <div class="party-summary-grid">
            <div>
                <div class="label">Code</div>
                <div class="value">{{ $party->party_code }}</div>
            </div>
            <div>
                <div class="label">Type</div>
                <div class="value">{{ ucfirst($party->type) }}</div>
            </div>
            <div>
                <div class="label">Mobile</div>
                <div class="value">{{ $party->mobile ?: '—' }}</div>
            </div>
            <div>
                <div class="label">Email</div>
                <div class="value">{{ $party->email ?: '—' }}</div>
            </div>
            @if($party->gstin)
            <div>
                <div class="label">GSTIN</div>
                <div class="value">{{ $party->gstin }}</div>
            </div>
            @endif
            <div>
                <div class="label">Status</div>
                <div class="value">
                    <span class="badge bg-{{ $party->is_active ? 'success' : 'secondary' }}">
                        {{ $party->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
            </div>
            <div>
                <div class="label">Closing Balance</div>
                <div class="value">
                    &#8377; {{ number_format($ledger['closing_balance'], 2) }}
                    <span class="badge bg-{{ $ledger['closing_type'] === 'debit' ? 'primary' : 'success' }} ms-1">
                        @drCr($ledger['closing_type'])
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h5 class="card-title mb-0">Transaction History</h5>
                <small class="text-muted">
                    Debit &#8377; {{ number_format($ledger['total_debit'], 2) }}
                    &middot; Credit &#8377; {{ number_format($ledger['total_credit'], 2) }}
                    &middot; Closing &#8377; {{ number_format($ledger['closing_balance'], 2) }}
                    @drCr($ledger['closing_type'])
                </small>
            </div>
            <div class="btn-group" role="group">
                <a href="{{ route('admin.parties.export-excel', array_filter(['party' => $party->id, 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
                   class="btn btn-success btn-sm" title="Export to Excel">
                    <i class="bi bi-file-earmark-excel me-1"></i>Excel
                </a>
                <a href="{{ route('admin.parties.export-pdf', array_filter(['party' => $party->id, 'date_from' => request('date_from'), 'date_to' => request('date_to')])) }}"
                   class="btn btn-danger btn-sm" title="Export to PDF">
                    <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                </a>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover party-history-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Voucher #</th>
                        <th>Particulars</th>
                        <th class="text-end">Debit (&#8377;)</th>
                        <th class="text-end">Credit (&#8377;)</th>
                        <th class="text-end">Balance (&#8377;)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledger['rows'] as $row)
                        @php($entry = $row['entry'])
                        <tr>
                            <td class="text-muted">@istDate($entry->transaction_date)</td>
                            <td>
                                @if($entry->voucher)
                                    <a href="{{ route('admin.vouchers.show', $entry->voucher->id) }}" class="report-detail-link" title="View voucher">
                                        {{ $entry->voucher->voucher_number }}
                                    </a>
                                @else
                                    <span class="text-muted">&mdash;</span>
                                @endif
                            </td>
                            <td class="particulars-cell" title="{{ $entry->description ?: ($entry->voucher->narration ?? '') }}">
                                {{ Str::limit($entry->description ?: ($entry->voucher->narration ?? '—'), 80) }}
                            </td>
                            <td class="text-end tabular-nums">{{ $entry->debit > 0 ? number_format($entry->debit, 2) : '—' }}</td>
                            <td class="text-end tabular-nums">{{ $entry->credit > 0 ? number_format($entry->credit, 2) : '—' }}</td>
                            <td class="text-end fw-semibold tabular-nums">
                                {{ number_format($row['running_balance'], 2) }}
                                <small class="text-muted fw-normal">@drCr($row['running_type'])</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No transactions found for this party.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if(count($ledger['rows']) > 0)
                <tfoot>
                    <tr class="fw-semibold">
                        <td colspan="3" class="text-end text-muted">Total</td>
                        <td class="text-end tabular-nums">{{ number_format($ledger['total_debit'], 2) }}</td>
                        <td class="text-end tabular-nums">{{ number_format($ledger['total_credit'], 2) }}</td>
                        <td class="text-end tabular-nums">
                            {{ number_format($ledger['closing_balance'], 2) }}
                            <small class="text-muted fw-normal">@drCr($ledger['closing_type'])</small>
                        </td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
        @if(!empty($ledger['paginator']))
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
            <small class="text-muted">
                Showing {{ $ledger['paginator']->firstItem() ?? 0 }}
                to {{ $ledger['paginator']->lastItem() ?? 0 }}
                of {{ $ledger['paginator']->total() }} entries
            </small>
            @if($ledger['paginator']->hasPages())
            <div>
                {{ $ledger['paginator']->withQueryString()->links() }}
            </div>
            @endif
        </div>
        @endif
    </div>
</div>

@if(in_array($party->type, ['debtor', 'creditor'], true) && $outstandingInvoices->isNotEmpty())
<div class="modal fade" id="multiInvoicePaymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $party->type === 'debtor' ? 'Record Receipt' : 'Record Payment' }} &mdash; {{ $party->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="multiInvoicePaymentForm">
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ $party->type === 'debtor' ? 'Received In' : 'Paid From' }} <span class="text-danger">*</span></label>
                            <select class="form-select" id="mip_cash_bank_account_id" name="cash_bank_account_id" required>
                                <option value="">Select Cash / Bank</option>
                                @foreach($cashBankAccounts as $option)
                                <option value="{{ $option['id'] }}">{{ $option['text'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="mip_payment_date" name="payment_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:2.5rem;"><input type="checkbox" id="mip_select_all" class="form-check-input"></th>
                                    <th>Invoice #</th>
                                    <th>Date</th>
                                    <th class="text-end">Balance Due</th>
                                    <th class="text-end" style="width:11rem;">Allocate Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($outstandingInvoices as $inv)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input mip-invoice-check" value="{{ $inv->id }}"
                                               data-balance="{{ $inv->balance_due }}">
                                    </td>
                                    <td>{{ $inv->invoice_number }}</td>
                                    <td>{{ $inv->invoice_date?->format('d-M-Y') }}</td>
                                    <td class="text-end">₹{{ number_format($inv->balance_due, 2) }}</td>
                                    <td class="text-end">
                                        <input type="number" class="form-control form-control-sm text-end mip-invoice-amount"
                                               data-invoice-id="{{ $inv->id }}" step="0.01" min="0" max="{{ $inv->balance_due }}"
                                               value="{{ $inv->balance_due }}" disabled>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end fw-semibold">
                        Total Allocated: ₹<span id="mip_total_allocated">0.00</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@if(in_array($party->type, ['debtor', 'creditor'], true) && $outstandingInvoices->isNotEmpty())
@section('scripts')
<script>
function mipRecalculateTotal() {
    let total = 0;
    $('.mip-invoice-check:checked').each(function () {
        const amount = parseFloat($('.mip-invoice-amount[data-invoice-id="' + $(this).val() + '"]').val()) || 0;
        total += amount;
    });
    $('#mip_total_allocated').text(total.toFixed(2));
}

$('.mip-invoice-check').on('change', function () {
    const $amountInput = $('.mip-invoice-amount[data-invoice-id="' + $(this).val() + '"]');
    $amountInput.prop('disabled', !this.checked);
    mipRecalculateTotal();
});

$('.mip-invoice-amount').on('input', mipRecalculateTotal);

$('#mip_select_all').on('change', function () {
    $('.mip-invoice-check').prop('checked', this.checked).trigger('change');
});

$('#multiInvoicePaymentForm').on('submit', function (e) {
    e.preventDefault();

    const allocations = [];
    $('.mip-invoice-check:checked').each(function () {
        const invoiceId = $(this).val();
        const amount = parseFloat($('.mip-invoice-amount[data-invoice-id="' + invoiceId + '"]').val()) || 0;
        if (amount > 0) {
            allocations.push({ invoice_id: invoiceId, amount: amount });
        }
    });

    if (!allocations.length) {
        toastr.error('Select at least one invoice and enter an allocation amount.');
        return;
    }

    $.ajax({
        url: '{{ route("admin.parties.record-payment", $party->id) }}',
        type: 'POST',
        data: {
            cash_bank_account_id: $('#mip_cash_bank_account_id').val(),
            payment_date: $('#mip_payment_date').val(),
            allocations: allocations,
        },
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function (r) {
            toastr.success(r.message);
            setTimeout(() => window.location.reload(), 1000);
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error recording payment');
        }
    });
});
</script>
@endsection
@endif
