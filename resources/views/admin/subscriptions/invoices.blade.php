@extends('layouts.app')

@section('title', 'Subscription Invoices')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Subscription Invoices</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.subscriptions.current') }}" class="btn btn-outline-primary me-2">
            <i class="bi bi-box me-1"></i>Current Plan
        </a>
        <a href="{{ route('admin.subscriptions.plans') }}" class="btn btn-outline-secondary">
            <i class="bi bi-grid me-1"></i>Plans
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-1">Invoice History</h5>
        <p class="text-muted small mb-0">Track billing cycles, due dates, and payment status for your active plan.</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Invoice #</th>
                        <th>Date</th>
                        <th>Due Date</th>
                        <th>Subtotal</th>
                        <th>Tax</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Paid At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                    <tr>
                        <td>{{ ($invoices->firstItem() ?? 1) + $loop->index }}</td>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td>{{ $invoice->invoice_date?->format('d-M-Y') }}</td>
                        <td>{{ $invoice->due_date?->format('d-M-Y') }}</td>
                        <td>₹{{ number_format($invoice->subtotal, 2) }}</td>
                        <td>₹{{ number_format($invoice->tax_amount, 2) }}</td>
                        <td><strong>₹{{ number_format($invoice->total, 2) }}</strong></td>
                        <td>
                            @php
                            $colors = ['draft'=>'secondary','sent'=>'info','paid'=>'success','overdue'=>'danger','cancelled'=>'dark'];
                            @endphp
                            <span class="badge bg-{{ $colors[$invoice->status] ?? 'secondary' }}">{{ ucfirst($invoice->status) }}</span>
                        </td>
                        <td>{{ $invoice->paid_at?->format('d-M-Y H:i') ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No invoices yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pt-3">
            {{ $invoices->links() }}
        </div>
    </div>
</div>
@endsection
