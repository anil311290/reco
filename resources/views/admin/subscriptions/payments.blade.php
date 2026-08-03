@extends('layouts.app')

@section('title', 'Subscription Payments')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Payment History</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.subscriptions.current') }}" class="btn btn-outline-primary">
            <i class="bi bi-box me-1"></i>Current Plan
        </a>
    </div>
</div>

<div class="card">
    <div class="card-header bg-white border-0 py-3">
        <h5 class="mb-1">Payment History</h5>
        <p class="text-muted small mb-0">Review completed and failed transactions for subscription renewals.</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Razorpay ID</th>
                        <th>Status</th>
                        <th>Failure Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $payment)
                    <tr>
                        <td>{{ ($payments->firstItem() ?? 1) + $loop->index }}</td>
                        <td>{{ $payment->created_at?->format('d-M-Y H:i') }}</td>
                        <td><strong>₹{{ number_format($payment->amount, 2) }}</strong></td>
                        <td>{{ ucfirst($payment->payment_method ?? 'Online') }}</td>
                        <td><code>{{ $payment->razorpay_payment_id ?? '-' }}</code></td>
                        <td>
                            @php
                            $colors = ['pending'=>'warning','processing'=>'info','completed'=>'success','failed'=>'danger','refunded'=>'primary'];
                            @endphp
                            <span class="badge bg-{{ $colors[$payment->status] ?? 'secondary' }}">{{ ucfirst($payment->status) }}</span>
                        </td>
                        <td>{{ $payment->failure_reason ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No payments yet</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="pt-3">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
