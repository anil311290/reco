@extends('layouts.app')

@section('title', 'Account Master')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Account Master</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <div class="btn-group me-2" role="group">
            @permission('accounts.export')
            <a href="{{ route('admin.accounts.export-excel') }}" class="btn btn-success btn-sm" title="Export to Excel">
                <i class="bi bi-file-earmark-excel me-1"></i>Excel
            </a>
            <a href="{{ route('admin.accounts.export-pdf') }}" class="btn btn-danger btn-sm" title="Export to PDF">
                <i class="bi bi-file-earmark-pdf me-1"></i>PDF
            </a>
            @endpermission
        </div>
        @permission('accounts.create')
        <a href="{{ route('admin.accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Account
        </a>
        @endpermission
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or code...">
            </div>
            <div class="col-md-3">
                <label for="account_type" class="form-label">Account Type</label>
                <select class="form-select" id="account_type" name="account_type">
                    <option value="">All Types</option>
                    <option value="asset">Asset</option>
                    <option value="liability">Liability</option>
                    <option value="income">Income</option>
                    <option value="expense">Expense</option>
                    <option value="equity">Equity</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="is_active" class="form-label">Status</label>
                <select class="form-select" id="is_active" name="is_active">
                    <option value="">All</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="accountsTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const table = loadDatatable('accountsTable', '{{ route("admin.accounts.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'account_code', name: 'account_code' },
        { data: 'account_name', name: 'account_name' },
        { 
            data: 'account_type',
            name: 'account_type',
            render: function(data) {
                const badges = {
                    'asset': 'bg-primary',
                    'liability': 'bg-danger',
                    'income': 'bg-success',
                    'expense': 'bg-warning',
                    'equity': 'bg-info'
                };
                const labels = {
                    'asset': 'Asset',
                    'liability': 'Liability',
                    'income': 'Income',
                    'expense': 'Expense',
                    'equity': 'Equity'
                };
                return `<span class="badge ${badges[data] || 'bg-secondary'}">${labels[data] || data}</span>`;
            }
        },
        { 
            data: 'opening_balance',
            name: 'opening_balance',
            render: function(data) {
                return formatCurrency(data || 0);
            }
        },
        {
            data: 'is_active',
            name: 'is_active',
            render: function(data, type, row) {
                const checked = data ? 'checked' : '';
                return `
                    <div class="form-check form-switch">
                        <input class="form-check-input status-toggle" type="checkbox" 
                               data-id="${row.id}" ${checked}>
                        <label class="form-check-label">
                            ${data ? 'Active' : 'Inactive'}
                        </label>
                    </div>
                `;
            }
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data) {
                let actions = `
                    <div class="btn-group btn-group-sm">
                        <a href="/admin/audit-logs?module=accounts&record_id=${data.id}" class="btn btn-outline-info" title="View Logs">
                            <i class="bi bi-journal-text"></i>
                        </a>
                        <a href="/admin/accounts/${data.id}/edit" class="btn btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                `;
                
                if (!data.is_system) {
                    actions += `
                        <button class="btn btn-outline-danger delete-btn" data-id="${data.id}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    `;
                }
                
                actions += `</div>`;
                return actions;
            }
        }
    ], {
        ajax: {
            data: function(d) {
                d.search = $('#search').val();
                d.account_type = $('#account_type').val();
                d.is_active = $('#is_active').val();
            }
        }
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Status toggle
    $('#accountsTable').on('change', '.status-toggle', function() {
        const id = $(this).data('id');
        const status = $(this).prop('checked') ? 1 : 0;
        changeStatus(`/admin/accounts/${id}/status`, !status, 'account', function() {
            table.ajax.reload();
        });
    });

    // Delete button
    $('#accountsTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        
        deleteRecord(`/admin/accounts/${id}`, 'account', function() {
            table.ajax.reload();
        });
    });
});
</script>
@endpush
