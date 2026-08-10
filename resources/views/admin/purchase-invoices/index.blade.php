@extends('layouts.app')

@section('title', 'Purchase Invoices')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Purchase Invoices</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @permission('vouchers.create')
        <a href="{{ route('admin.purchase-invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>New Invoice
        </a>
        @endpermission
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" id="search" placeholder="Invoice # or supplier...">
            </div>
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select class="form-select" id="status">
                    <option value="">All</option>
                    <option value="draft">Draft</option>
                    <option value="verified">Verified</option>
                    <option value="partial">Partial</option>
                    <option value="paid">Paid</option>
                    <option value="overdue">Overdue</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-2"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="invoicesTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Supplier Ref</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Balance</th>
                        <th>Status</th>
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
$(document).ready(function() {
    let table = $('#invoicesTable').DataTable({
        processing: true,
        serverSide: false,
        dom: '<"row"<"col-sm-12 col-md-4"l><"col-sm-12 col-md-4"f><"col-sm-12 col-md-4 text-md-end"B>>rtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i>Excel',
                className: 'btn btn-success btn-sm dt-export-btn dt-export-excel',
                exportOptions: {
                    columns: window.datatableExportableColumn
                },
                messageTop: window.buildDatatableExportMetaText
            },
            window.buildPdfButtonConfig()
        ],
        ajax: {
            url: '{{ route("admin.purchase-invoices.index") }}',
            data: function(d) {
                d.search = $('#search').val();
                d.status = $('#status').val();
            }
        },
        columns: [
            { data: 'invoice_number' },
            { data: 'supplier_invoice_number' },
            istDateColumn('invoice_date'),
            { data: 'party', render: function(data) { return data ? data.name : '-'; }},
            { data: 'total', render: function(d) { return '₹' + parseFloat(d||0).toFixed(2); }},
            { data: 'amount_paid', render: function(d) { return '₹' + parseFloat(d||0).toFixed(2); }},
            { data: 'balance_due', render: function(d) {
                let val = parseFloat(d||0);
                return val > 0 ? `<span class="text-danger">₹${val.toFixed(2)}</span>` : `₹${val.toFixed(2)}`;
            }},
            { data: 'status', render: function(data) {
                let colors = {draft:'secondary',verified:'info',partial:'warning',paid:'success',overdue:'danger',cancelled:'dark'};
                return `<span class="badge bg-${colors[data]||'secondary'}">${data.charAt(0).toUpperCase()+data.slice(1)}</span>`;
            }},
            { data: null, orderable: false, render: function(data) {
                let buttons = `<a href="/admin/purchase-invoices/${data.id}" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a>`;
                if (data.status !== 'cancelled' && data.status !== 'paid') {
                    buttons += ` <a href="/admin/purchase-invoices/${data.id}?open_payment=1" class="btn btn-sm btn-outline-success" title="Record Payment"><i class="bi bi-cash"></i></a>`;
                }
                if (data.status !== 'cancelled' && data.status !== 'paid' && data.status !== 'partial') {
                    buttons += ` <a href="/admin/purchase-invoices/${data.id}/edit" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>`;
                }
                return `<div class="d-flex gap-1">${buttons}</div>`;
            }}
        ],
        order: [[2, 'desc']]
    });

    $('#filterForm').on('submit', function(e) { e.preventDefault(); table.ajax.reload(); });
});
</script>
@endsection
