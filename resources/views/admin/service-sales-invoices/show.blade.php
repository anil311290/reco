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
                            <span class="badge bg-{{ $invoice->status === 'draft' ? 'secondary' : 'success' }}">
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
                <div class="d-flex justify-content-between">
                    <span class="fw-bold">Total:</span>
                    <span class="fw-bold text-primary">₹{{ number_format($invoice->total, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
