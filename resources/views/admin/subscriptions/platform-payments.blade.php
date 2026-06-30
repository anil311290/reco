@extends('layouts.app')

@section('title', 'Platform Payments')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Platform Payments</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @if(!empty($selectedCompany))
            <a href="{{ route('admin.platform-subscriptions.payments') }}" class="btn btn-outline-primary me-2">
                <i class="bi bi-funnel me-2"></i>Clear Filter
            </a>
        @endif
        <a href="{{ route('admin.platform-subscriptions.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Subscriptions
        </a>
    </div>
</div>

@if(!empty($selectedCompany))
<div class="alert alert-info">
    <i class="bi bi-info-circle me-2"></i>
    Showing payments for <strong>{{ $selectedCompany->name }}</strong>.
</div>
@endif

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="platformPaymentsTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th>Razorpay ID</th>
                        <th>Paid At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const ajaxUrl = '{{ route("admin.platform-subscriptions.payments", array_filter(["company_id" => optional($selectedCompany)->id])) }}';

    loadDatatable('platformPaymentsTable', ajaxUrl, [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'company_name', name: 'company.name', render: function(data, type, row) {
            const url = '/admin/companies/' + row.company_id;
            return '<a href="' + url + '" class="fw-semibold text-decoration-none">' + data + '</a>';
        }},
        { data: 'plan_name', name: 'subscription.plan.name' },
        { data: 'amount_formatted', name: 'amount' },
        { data: 'status_badge', name: 'status', orderable: false, searchable: false },
        { data: 'payment_method', name: 'payment_method' },
        { data: 'razorpay_payment_id_formatted', name: 'razorpay_payment_id' },
        { data: 'paid_at_formatted', name: 'paid_at' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
    ], {
        order: [[7, 'desc']]
    });
});
</script>
@endsection