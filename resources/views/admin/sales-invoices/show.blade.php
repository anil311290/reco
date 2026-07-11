@extends('layouts.app')

@section('title', 'Sales Invoice ' . $invoice->invoice_number)

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Sales Invoice {{ $invoice->invoice_number }}</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.sales-invoices.index') }}" class="btn btn-outline-secondary me-2">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
        @if(!$invoice->isPaid())
        <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="bi bi-cash me-1"></i>Record Payment
        </button>
        @endif
        <a href="{{ route('admin.sales-invoices.pdf', $invoice->id) }}" class="btn btn-outline-danger" target="_blank">
            <i class="bi bi-file-pdf me-1"></i>PDF
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h6 class="text-muted">Bill To</h6>
                        <h5>{{ $invoice->party->name ?? 'N/A' }}</h5>
                        @if($invoice->party)
                        <p class="mb-0">{{ $invoice->party->address }}</p>
                        <p class="mb-0">{{ $invoice->party->city }}, {{ $invoice->party->state }}</p>
                        @if($invoice->party->gstin)
                        <p class="mb-0">GSTIN: {{ $invoice->party->gstin }}</p>
                        @endif
                        @endif
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h6 class="text-muted">Invoice Details</h6>
                        <p class="mb-0"><strong>Invoice #:</strong> {{ $invoice->invoice_number }}</p>
                        <p class="mb-0"><strong>Date:</strong> {{ $invoice->invoice_date?->format('d M Y') }}</p>
                        <p class="mb-0"><strong>Due Date:</strong> {{ $invoice->due_date?->format('d M Y') }}</p>
                        @if($invoice->reference_number)
                        <p class="mb-0"><strong>Ref:</strong> {{ $invoice->reference_number }}</p>
                        @endif
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
                                <td>
                                    {{ $line->item->name ?? $line->description }}
                                    @if($line->description && $line->item)
                                    <br><small class="text-muted">{{ $line->description }}</small>
                                    @endif
                                </td>
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
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h6 class="mb-0">Invoice Status</h6></div>
            <div class="card-body">
                @php
                $statusColors = ['draft'=>'secondary','sent'=>'info','partial'=>'warning','paid'=>'success','overdue'=>'danger','cancelled'=>'dark','credit_note'=>'primary'];
                @endphp
                <span class="badge bg-{{ $statusColors[$invoice->status] ?? 'secondary' }} fs-6 mb-3">{{ ucfirst($invoice->status) }}</span>

                @if($invoice->isOverdue())
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i>This invoice is overdue by {{ now()->diffInDays($invoice->due_date) }} days
                </div>
                @endif

                <hr>
                <p class="mb-1"><strong>Created:</strong> {{ $invoice->created_at?->format('d M Y H:i') }}</p>
                <p class="mb-1"><strong>Updated:</strong> {{ $invoice->updated_at?->format('d M Y H:i') }}</p>
                @if($invoice->financialYear)
                <p class="mb-0"><strong>Financial Year:</strong> {{ $invoice->financialYear->name }}</p>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
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
                        <label class="form-label">Payment Amount <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="amount" step="0.01" min="0.01" max="{{ $invoice->balance_due }}" value="{{ $invoice->balance_due }}" required>
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

@section('scripts')
<script>
$('#paymentForm').on('submit', function(e) {
    e.preventDefault();
    $.ajax({
        url: '{{ route("admin.sales-invoices.payment", $invoice->id) }}',
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
