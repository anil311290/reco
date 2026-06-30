@extends('layouts.app')

@section('title', 'Platform Subscriptions')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Platform Subscriptions</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.platform-subscriptions.payments') }}" class="btn btn-primary">
            <i class="bi bi-cash-coin me-2"></i>View Payments
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="platformSubscriptionsTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Company</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Cycle</th>
                        <th>Amount</th>
                        <th>Period End</th>
                        <th>Created</th>
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
    loadDatatable('platformSubscriptionsTable', '{{ route("admin.platform-subscriptions.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'company_name', name: 'company.name', render: function(data, type, row) {
            const url = '/admin/companies/' + row.company_id;
            return '<a href="' + url + '" class="fw-semibold text-decoration-none">' + data + '</a>';
        }},
        { data: 'plan_name', name: 'plan.name' },
        { data: 'status_badge', name: 'status', orderable: false, searchable: false },
        { data: 'billing_cycle', name: 'billing_cycle' },
        { data: 'amount_formatted', name: 'amount' },
        { data: 'period_end_formatted', name: 'current_period_end' },
        { data: 'created_at_formatted', name: 'created_at' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
    ], {
        order: [[7, 'desc']]
    });
});
</script>
@endsection