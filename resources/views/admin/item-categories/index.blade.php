@extends('layouts.app')

@section('title', 'Item Categories')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Item Category Master</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @permission('accounts.create')
        <button type="button" class="btn btn-primary" id="addCategoryBtn">
            <i class="bi bi-plus-circle me-2"></i>Add Category
        </button>
        @endpermission
    </div>
</div>

{{-- Create / Edit Modal --}}
<div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoryModalLabel">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="itemCategoryForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="sort_order" class="form-label">Sort Order</label>
                            <input type="number" class="form-control" id="sort_order" name="sort_order" min="0" value="0">
                        </div>
                        <div class="col-md-6">
                            <label for="is_active" class="form-label">Status</label>
                            <select class="form-select" id="is_active" name="is_active">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveCategoryBtn">
                        <i class="bi bi-check-circle me-2"></i>Save Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <div class="col-md-8">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or code...">
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
            <table id="itemCategoriesTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Sort Order</th>
                        <th>Items</th>
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

@push('scripts')
<script>
$(document).ready(function() {
    const table = loadDatatable('itemCategoriesTable', '{{ route("admin.item-categories.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'name', name: 'name' },
        { data: 'sort_order', name: 'sort_order' },
        { data: 'items_count', name: 'items_count', orderable: false, searchable: false },
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
                @permission('accounts.edit')
                actions += `<button class="btn btn-outline-primary edit-btn" 
                    data-id="${data.id}"
                    data-name="${data.name}"
                    data-sort_order="${data.sort_order ?? 0}"
                    data-description="${(data.description || '').replace(/"/g, '&quot;')}"
                    data-is_active="${data.is_active ? 1 : 0}"
                    title="Edit"><i class="bi bi-pencil"></i></button>`;
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
        order: [[1, 'desc']],
        ajax: {
            data: function(d) {
                d.search = $('#search').val();
                d.is_active = $('#is_active').val();
            }
        }
    });

    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    $('#itemCategoriesTable').on('change', '.status-toggle', function() {
        const id = $(this).data('id');
        const status = $(this).prop('checked') ? 1 : 0;
        changeStatus(`/admin/item-categories/${id}/status`, !status, 'item category', function() {
            table.ajax.reload();
        });
    });

    $('#itemCategoriesTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        deleteRecord(`/admin/item-categories/${id}`, 'item category', function() {
            table.ajax.reload();
        });
    });

    // Open modal for Create
    $('#addCategoryBtn').on('click', function() {
        $('#categoryModalLabel').text('Add Category');
        $('#saveCategoryBtn').html('<i class="bi bi-check-circle me-2"></i>Save Category');
        $('#itemCategoryForm')[0].reset();
        $('#sort_order').val(0);
        $('#is_active').val(1);
        ajaxFormSubmit('#itemCategoryForm', '{{ route("admin.item-categories.store") }}', 'POST', function() {
            $('#categoryModal').modal('hide');
            table.ajax.reload();
        });
        $('#categoryModal').modal('show');
    });

    // Open modal for Edit
    $('#itemCategoriesTable').on('click', '.edit-btn', function() {
        const btn = $(this);
        const id = btn.data('id');
        $('#categoryModalLabel').text('Edit Category');
        $('#saveCategoryBtn').html('<i class="bi bi-check-circle me-2"></i>Update Category');
        $('#itemCategoryForm')[0].reset();
        $('#name').val(btn.data('name'));
        $('#sort_order').val(btn.data('sort_order'));
        $('#description').val(btn.data('description'));
        $('#is_active').val(btn.data('is_active'));
        ajaxFormSubmit('#itemCategoryForm', `/admin/item-categories/${id}`, 'PUT', function() {
            $('#categoryModal').modal('hide');
            table.ajax.reload();
        });
        $('#categoryModal').modal('show');
    });
});
</script>
@endpush
