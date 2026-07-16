@extends('layouts.app')

@section('title', 'Service Sales Invoice Details')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Service Sales Invoice #{{ $invoice->invoice_number }}</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.service-sales-invoices.index') }}" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
        @if(!$invoice->isPaid())
        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="bi bi-cash me-1"></i>Record Payment
        </button>
        @endif
        @permission('invoices.update')
        <a href="{{ route('admin.service-sales-invoices.edit', $invoice->id) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-2"></i>Edit
        </a>
        @endpermission
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Invoice Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label text-muted">Invoice Date</label>
                        <p class="mb-0">{{ $invoice->invoice_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Due Date</label>
                        <p class="mb-0">{{ $invoice->due_date->format('d/m/Y') }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Customer</label>
                        <p class="mb-0">{{ $invoice->party->name }}</p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted">Status</label>
                        <p class="mb-0">
                            <span class="badge bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partial' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <strong>₹{{ number_format($invoice->subtotal, 2) }}</strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax:</span>
                    <span>₹{{ number_format($invoice->tax_amount, 2) }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-2">
                    <span class="fw-bold">Total:</span>
                    <span class="fw-bold text-primary">₹{{ number_format($invoice->total, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Paid:</span>
                    <span class="text-success">₹{{ number_format($invoice->amount_paid, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span>Balance Due:</span>
                    <span class="text-danger fw-bold">₹{{ number_format($invoice->balance_due, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@if(!$invoice->isPaid())
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
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
                        <label class="form-label">Payment Mode <span class="text-danger">*</span></label>
                        <select class="form-select" id="payment_mode" name="payment_mode" required>
                            <option value="">Select Mode</option>
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="od">OD</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Received In <span class="text-danger">*</span></label>
                        <select class="form-select" id="cash_bank_account_id" name="cash_bank_account_id" required>
                            <option value="">Select Cash / Bank</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" value="{{ $invoice->balance_due }}" required>
                    </div>
                    <small class="text-muted">Posts a Receipt voucher (Dr Cash/Bank, Cr Party) linked to this invoice.</small>
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
    const mode = $('#payment_mode').val();
    let html = '<option value="">Select Cash / Bank</option>';
    cashBankAccounts.forEach((option) => {
        if (mode && option.transaction_mode !== mode) {
            return;
        }
        html += `<option value="${option.id}">${option.text}</option>`;
    });
    $('#cash_bank_account_id').html(html);
}

$('#payment_mode').on('change', refreshCashBankDropdown);

$('#paymentForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: '{{ route("admin.service-sales-invoices.payment", $invoice->id) }}',
        type: 'POST',
        data: $(this).serialize(),
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
        success: function(r) {
            toastr.success(r.message);
            setTimeout(() => location.reload(), 1000);
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error recording payment');
        }
    });
});
</script>
@endsection
