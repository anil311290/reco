@extends('layouts.app')

@section('title', 'Service Sales Invoices')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Service Sales Invoices</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.service-sales-invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Create Invoice
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table table-striped datatable" id="serviceInvoicesTable">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    loadDatatable('serviceInvoicesTable', '{{ route("admin.service-sales-invoices.index") }}', [
        { data: 'invoice_number' },
        istDateColumn('invoice_date'),
        { data: 'party', render: function(data) { return data ? data.name : '-'; } },
        { data: 'total', render: function(d) { return '₹' + parseFloat(d || 0).toFixed(2); } },
        { data: 'status', render: function(data) {
            let colors = {draft:'secondary', sent:'info', partial:'warning', paid:'success', overdue:'danger', cancelled:'dark'};
            return `<span class="badge bg-${colors[data] || 'secondary'}">${data ? data.charAt(0).toUpperCase() + data.slice(1) : '-'}</span>`;
        }},
        { data: null, orderable: false, render: function(data) {
            return `<div class="d-flex gap-1">
                <a href="/admin/service-sales-invoices/${data.id}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>
                <a href="/admin/service-sales-invoices/${data.id}/edit" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
            </div>`;
        }}
    ], { order: [[1, 'desc']] });
});
</script>
@endpush
