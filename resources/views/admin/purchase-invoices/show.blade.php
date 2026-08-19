@extends('layouts.app')

@section('title', 'Purchase Invoice ' . $invoice->invoice_number)

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Purchase Invoice {{ $invoice->invoice_number }}</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.purchase-invoices.index') }}" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        @if($invoice->status === 'draft')
        <button type="button" class="btn btn-primary me-2" id="postInvoiceBtn">
            <i class="bi bi-send-check me-1"></i>Post Invoice
        </button>
        @endif
        @if($invoice->status !== 'cancelled' && $invoice->status !== 'draft' && !$invoice->isPaid())
        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="bi bi-cash me-1"></i>Record Payment
        </button>
        @endif
        @if($invoice->status !== 'cancelled')
        <button type="button" class="btn btn-outline-warning" id="cancelInvoiceBtn">
            <i class="bi bi-x-circle me-1"></i>Cancel Invoice
        </button>
        @endif
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Supplier</h6>
                        <h5>{{ $invoice->party->name ?? 'N/A' }}</h5>
                        @if($invoice->party)
                        <p class="mb-0">{{ $invoice->party->address }}</p>
                        <p class="mb-0">{{ $invoice->party->city }}, {{ $invoice->party->state }}</p>
                        @if($invoice->party->gst_number)
                        <p class="mb-0">GSTIN: {{ $invoice->party->gst_number }}</p>
                        @endif
                        @endif
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="text-muted">Invoice Details</h6>
                        <p class="mb-0"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                        @if($invoice->supplier_invoice_number)
                        <p class="mb-0"><strong>Supplier Ref:</strong> {{ $invoice->supplier_invoice_number }}</p>
                        @endif
                        <p class="mb-0"><strong>Date:</strong> {{ $invoice->invoice_date?->format('d-M-Y') }}</p>
                        <p class="mb-0"><strong>Due Date:</strong> {{ $invoice->due_date?->format('d-M-Y') }}</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Description</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">Disc %</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->lines as $index => $line)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $line->item->name ?? $line->description }}</td>
                                <td class="text-end">{{ number_format($line->quantity, 2) }}</td>
                                <td class="text-end">₹{{ number_format($line->unit_price, 2) }}</td>
                                <td class="text-end">{{ $line->discount_percentage }}%</td>
                                <td class="text-end">₹{{ number_format($line->tax_amount, 2) }}</td>
                                <td class="text-end">₹{{ number_format($line->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        @if($invoice->notes)
                        <p class="text-muted"><strong>Notes:</strong> {{ $invoice->notes }}</p>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr><td>Subtotal</td><td class="text-end">₹{{ number_format($invoice->subtotal, 2) }}</td></tr>
                            @if($invoice->discount_amount > 0)
                            <tr><td>Discount</td><td class="text-end text-danger">-₹{{ number_format($invoice->discount_amount, 2) }}</td></tr>
                            @endif
                            <tr><td>Tax</td><td class="text-end">₹{{ number_format($invoice->tax_amount, 2) }}</td></tr>
                            <tr class="fw-bold fs-5"><td>Total</td><td class="text-end text-primary">₹{{ number_format($invoice->total, 2) }}</td></tr>
                            <tr><td>Paid</td><td class="text-end text-success">₹{{ number_format($invoice->amount_paid, 2) }}</td></tr>
                            @if($invoice->balance_due > 0)
                            <tr class="fw-bold"><td>Balance Due</td><td class="text-end text-danger">₹{{ number_format($invoice->balance_due, 2) }}</td></tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>

        @php $settlements = $invoice->getSettlementDetails(); @endphp
        @if($settlements->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history me-1"></i>Settlement History</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Payment #</th>
                                <th>Date</th>
                                <th class="text-end">Amount Settled</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($settlements as $settlement)
                            <tr>
                                <td>
                                    @if($settlement->paymentVoucher)
                                    <a href="{{ route('admin.vouchers.show', $settlement->paymentVoucher->id) }}">{{ $settlement->paymentVoucher->voucher_number }}</a>
                                    @else
                                    N/A
                                    @endif
                                </td>
                                <td>{{ $settlement->paymentVoucher?->voucher_date?->format('d-M-Y') ?? '-' }}</td>
                                <td class="text-end">₹{{ number_format((float) $settlement->amount_settled, 2) }}</td>
                                <td>
                                    @php $settlementColors = ['pending'=>'secondary','partial'=>'warning','full'=>'success','reversed'=>'dark']; @endphp
                                    <span class="badge bg-{{ $settlementColors[$settlement->status] ?? 'secondary' }}">{{ ucfirst($settlement->status) }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Invoice Status</h6></div>
            <div class="card-body">
                @php
                $statusColors = ['draft'=>'secondary','verified'=>'info','partial'=>'warning','paid'=>'success','overdue'=>'danger','cancelled'=>'dark'];
                @endphp
                <span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }} fs-6 mb-3">{{ ucfirst($invoice->status) }}</span>

                @if($invoice->isOverdue())
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>This invoice is overdue
                </div>
                @endif

                <hr>
                <p class="mb-1"><strong>Created:</strong> {{ $invoice->created_at?->format('d-M-Y H:i') }}</p>
                <p class="mb-0"><strong>Updated:</strong> {{ $invoice->updated_at?->format('d-M-Y H:i') }}</p>
            </div>
        </div>
    </div>
</div>

@if($invoice->status !== 'cancelled' && $invoice->status !== 'draft' && !$invoice->isPaid())
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="paymentForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Balance Due: <strong class="text-danger">₹{{ number_format($invoice->balance_due, 2) }}</strong></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="payment_date" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Paid From <span class="text-danger">*</span></label>
                        <select class="form-select" id="cash_bank_account_id" name="cash_bank_account_id" required>
                            <option value="">Select Cash / Bank</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" value="{{ $invoice->balance_due }}" required>
                    </div>
                    <small class="text-muted">Posts a Payment voucher (Dr Party, Cr Cash/Bank) linked to this invoice.</small>
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

@section('scripts')
<script>
const cashBankAccounts = @json($cashBankAccounts ?? []);

function refreshCashBankDropdown() {
    let html = '<option value="">Select Cash / Bank</option>';
    cashBankAccounts.forEach((option) => {
        html += `<option value="${option.id}">${option.text}</option>`;
    });
    $('#cash_bank_account_id').html(html);
}

refreshCashBankDropdown();

const shouldOpenPayment = new URLSearchParams(window.location.search).get('open_payment') === '1';
if (shouldOpenPayment && $('#paymentModal').length) {
    const paymentModalEl = document.getElementById('paymentModal');
    const paymentModal = new bootstrap.Modal(paymentModalEl);
    setTimeout(() => paymentModal.show(), 120);

    const url = new URL(window.location.href);
    url.searchParams.delete('open_payment');
    url.hash = '';
    window.history.replaceState({}, document.title, url.pathname + (url.search ? '?' + url.searchParams.toString() : ''));
}

$('#paymentForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: '{{ route("admin.purchase-invoices.payment", $invoice->id) }}',
        type: 'POST',
        data: $(this).serialize(),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(r) {
            toastr.success(r.message);
            setTimeout(() => {
                const cleanUrl = window.location.pathname;
                window.location.href = cleanUrl;
            }, 1000);
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error recording payment');
        }
    });
});

$('#postInvoiceBtn').on('click', function() {
    Swal.fire({
        title: 'Post this invoice?',
        text: 'This will generate the accounting voucher and post it to the ledger.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#0d6efd',
        confirmButtonText: 'Yes, post it'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        $.ajax({
            url: '{{ route("admin.purchase-invoices.post", $invoice->id) }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(r) {
                toastr.success(r.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error posting invoice');
            }
        });
    });
});

$('#cancelInvoiceBtn').on('click', function() {
    Swal.fire({
        title: 'Cancel this invoice?',
        text: 'Linked payments and purchase posting will be cancelled, ledgers reversed, and stock adjusted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f59e0b',
        confirmButtonText: 'Yes, cancel it'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        $.ajax({
            url: '{{ route("admin.purchase-invoices.cancel", $invoice->id) }}',
            type: 'POST',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(r) {
                toastr.success(r.message);
                setTimeout(() => location.reload(), 800);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error cancelling invoice');
            }
        });
    });
});
</script>
@endsection
