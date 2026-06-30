@extends('layouts.app')

@section('title', 'Party Master')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Party Master</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @permission('parties.create')
        <a href="{{ route('admin.parties.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Party
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
                <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, code, or mobile...">
            </div>
            <div class="col-md-3">
                <label for="type" class="form-label">Party Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All Types</option>
                    <option value="debtor">Debtor</option>
                    <option value="creditor">Creditor</option>
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
            <table id="partiesTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Mobile</th>
                        <th>Opening Balance</th>
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
    const table = loadDatatable('partiesTable', '{{ route("admin.parties.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'party_code', name: 'party_code' },
        { data: 'name', name: 'name' },
        { 
            data: 'type',
            name: 'type',
            render: function(data) {
                const badge = data === 'debtor' ? 'bg-success' : 'bg-danger';
                const label = data === 'debtor' ? 'Debtor' : 'Creditor';
                return `<span class="badge ${badge}">${label}</span>`;
            }
        },
        { data: 'mobile', name: 'mobile' },
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
                    </div>
                `;
            }
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data) {
                return `
                    <div class="btn-group btn-group-sm">
                        <a href="/admin/parties/${data.id}/edit" class="btn btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @permission('audit-logs.view')
                        <a href="{{ route('admin.audit-logs.index') }}?module=parties&record_id=${data.id}" class="btn btn-outline-info" title="Audit Logs">
                            <i class="bi bi-clock-history"></i>
                        </a>
                        @endpermission
                        <button class="btn btn-outline-danger delete-btn" data-id="${data.id}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
            }
        }
    ]);

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Status toggle
    $('#partiesTable').on('change', '.status-toggle', function() {
        const id = $(this).data('id');
        const status = $(this).prop('checked') ? 1 : 0;
        changeStatus(`/admin/parties/${id}/status`, !status, 'party', function() {
            table.ajax.reload();
        });
    });

    // Delete button
    $('#partiesTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        
        deleteRecord(`/admin/parties/${id}`, 'party', function() {
            table.ajax.reload();
        });
    });
});
</script>
@endpush
