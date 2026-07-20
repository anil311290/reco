@extends('layouts.app')

@section('title', 'Items')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Items & Services</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @permission('accounts.create')
        <a href="{{ route('admin.items.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add Item
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
                <input type="text" class="form-control" id="search" name="search" placeholder="Search by name, code or barcode...">
            </div>
            <div class="col-md-2">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="">All</option>
                    <option value="goods">Goods</option>
                    <option value="service">Service</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="category_id" class="form-label">Category</label>
                <select class="form-select" id="category_id" name="category_id">
                    <option value="">All</option>
                    @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
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
            <table id="itemsTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>HSN/SAC</th>
                        <th>Selling Price</th>
                        <th>Stock</th>
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
    const table = loadDatatable('itemsTable', '{{ route("admin.items.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'item_code', name: 'item_code' },
        {
            data: 'name',
            name: 'name',
            render: function(data, type, row) {
                return `<a href="/admin/items/${row.id}" class="report-detail-link" title="View details">${data}</a>`;
            }
        },
        {
            data: 'category',
            name: 'category.name',
            render: function(data) {
                return data && data.name ? data.name : '<span class="text-muted">-</span>';
            }
        },
        { 
            data: 'type',
            name: 'type',
            render: function(data) {
                return data === 'goods' ? '<span class="badge bg-primary">Goods</span>' : '<span class="badge bg-info">Service</span>';
            }
        },
        { data: 'hsn_sac_code', name: 'hsn_sac_code' },
        { 
            data: 'selling_price', 
            name: 'selling_price',
            render: function(data) { 
                return '₹' + parseFloat(data || 0).toFixed(2); 
            }
        },
        {
            data: 'current_stock',
            name: 'current_stock',
            render: function(data, type, row) {
                if (row.type === 'service' || row.is_stockable === false) {
                    return '<span class="text-muted">-</span>';
                }
                return parseFloat(data || 0).toFixed(3);
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
                let actions = `<div class="btn-group btn-group-sm">`;
                actions += `<a href="/admin/items/${data.id}" class="btn btn-outline-secondary" title="View Details">
                    <i class="bi bi-eye"></i>
                </a>`;
                @permission('accounts.edit')
                actions += `<a href="/admin/items/${data.id}/edit" class="btn btn-outline-primary" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>`;
                @endpermission
                @permission('accounts.delete')
                actions += `<button class="btn btn-outline-danger delete-btn" data-id="${data.id}" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>`;
                @endpermission
                actions += `</div>`;
                return actions;
            }
        }
    ], {
        ajax: {
            data: function(d) {
                d.search = $('#search').val();
                d.type = $('#type').val();
                d.category_id = $('#category_id').val();
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
    $('#itemsTable').on('change', '.status-toggle', function() {
        const id = $(this).data('id');
        const status = $(this).prop('checked') ? 1 : 0;
        changeStatus(`/admin/items/${id}/status`, !status, 'item', function() {
            table.ajax.reload();
        });
    });

    // Delete button
    $('#itemsTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        deleteRecord(`/admin/items/${id}`, 'item', function() {
            table.ajax.reload();
        });
    });
});
</script>
@endpush