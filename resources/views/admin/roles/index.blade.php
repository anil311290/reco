@extends('layouts.app')

@section('title', 'Roles Management')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Roles Management</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add New Role
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="rolesTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Permissions</th>
                        <th>Status</th>
                        <th>Default</th>
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
    const table = loadDatatable('rolesTable', '{{ route("admin.roles.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'name', name: 'name' },
        { data: 'slug', name: 'slug' },
        { 
            data: 'permissions',
            name: 'permissions',
            orderable: false,
            searchable: false,
            render: function(data) {
                if (!data || data.length === 0) {
                    return '<span class="badge bg-secondary">No permissions</span>';
                }
                return data.slice(0, 3).map(p => 
                    `<span class="badge bg-info me-1">${p.name}</span>`
                ).join('') + (data.length > 3 ? `<span class="badge bg-secondary">+${data.length - 3} more</span>` : '');
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
            data: 'is_default',
            name: 'is_default',
            render: function(data) {
                return data ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            }
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data) {
                return `
                    <div class="btn-group btn-group-sm">
                        <a href="/admin/roles/${data.id}/edit" class="btn btn-outline-primary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <button class="btn btn-outline-danger delete-btn" data-id="${data.id}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
            }
        }
    ]);

    // Status toggle
    $('#rolesTable').on('change', '.status-toggle', function() {
        const id = $(this).data('id');
        const status = $(this).prop('checked') ? 1 : 0;
        
        changeStatus(`/admin/roles/${id}/status`, !status, 'role', function() {
            table.ajax.reload();
        });
    });

    // Delete button
    $('#rolesTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        
        deleteRecord(`/admin/roles/${id}`, 'role', function() {
            table.ajax.reload();
        });
    });
});
</script>
@endpush
