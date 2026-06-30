@extends('layouts.app')

@section('title', 'Subscription Plans')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Subscription Plans Management</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @permission('settings.create')
        <button class="btn btn-primary" id="addPlanBtn">
            <i class="bi bi-plus-circle me-2"></i>Add Plan
        </button>
        @endpermission
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="plansTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Monthly</th>
                        <th>Yearly</th>
                        <th>Lifetime</th>
                        <th>Trial Days</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th>Visible</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Plan Modal -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="planForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="planModalTitle">Add Plan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="planId">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="planName" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="slug" id="planSlug" required>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="planDescription" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Monthly Price <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="monthly_price" id="monthlyPrice" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Yearly Price <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="yearly_price" id="yearlyPrice" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Lifetime Price</label>
                            <input type="number" class="form-control" name="lifetime_price" id="lifetimePrice" step="0.01" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Trial Days</label>
                            <input type="number" class="form-control" name="trial_days" id="trialDays" min="0" value="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Users <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="max_users" id="maxUsers" min="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Max Transactions <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="max_transactions" id="maxTransactions" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" class="form-control" name="sort_order" id="sortOrder" min="0" value="0">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const table = loadDatatable('plansTable', '{{ route("admin.subscription-plans.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'name', name: 'name' },
        { data: 'slug', name: 'slug' },
        { 
            data: 'monthly_price',
            name: 'monthly_price',
            render: function(data) {
                return data > 0 ? '₹' + parseFloat(data).toFixed(2) : '<span class="text-muted">Free</span>';
            }
        },
        { 
            data: 'yearly_price',
            name: 'yearly_price',
            render: function(data) {
                return data > 0 ? '₹' + parseFloat(data).toFixed(2) : '<span class="text-muted">Free</span>';
            }
        },
        { 
            data: 'lifetime_price',
            name: 'lifetime_price',
            render: function(data) {
                return data > 0 ? '₹' + parseFloat(data).toFixed(2) : '<span class="text-muted">-</span>';
            }
        },
        { data: 'trial_days', name: 'trial_days' },
        { 
            data: 'max_users',
            name: 'max_users',
            render: function(data) {
                return data == -1 ? '<span class="badge bg-success">Unlimited</span>' : data;
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
            data: 'is_visible',
            name: 'is_visible',
            render: function(data) {
                return data ? '<span class="badge bg-primary">Yes</span>' : '<span class="badge bg-secondary">No</span>';
            }
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data) {
                return `
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary edit-btn" data-id="${data.id}" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-outline-danger delete-btn" data-id="${data.id}" title="Delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;
            }
        }
    ]);

    // Add button
    $('#addPlanBtn').on('click', function() {
        $('#planForm')[0].reset();
        $('#planId').val('');
        $('#planModalTitle').text('Add Plan');
        $('#planModal').modal('show');
    });

    // Edit button
    $('#plansTable').on('click', '.edit-btn', function() {
        const id = $(this).data('id');
        $.get(`/admin/subscription-plans/${id}`, function(response) {
            const plan = response.data;
            $('#planId').val(plan.id);
            $('#planName').val(plan.name);
            $('#planSlug').val(plan.slug);
            $('#planDescription').val(plan.description);
            $('#monthlyPrice').val(plan.monthly_price);
            $('#yearlyPrice').val(plan.yearly_price);
            $('#lifetimePrice').val(plan.lifetime_price);
            $('#trialDays').val(plan.trial_days);
            $('#maxUsers').val(plan.max_users);
            $('#maxTransactions').val(plan.max_transactions);
            $('#sortOrder').val(plan.sort_order);
            $('#planModalTitle').text('Edit Plan');
            $('#planModal').modal('show');
        });
    });

    // Form submit
    $('#planForm').on('submit', function(e) {
        e.preventDefault();
        const id = $('#planId').val();
        const method = id ? 'PUT' : 'POST';
        const url = id ? `/admin/subscription-plans/${id}` : '{{ route("admin.subscription-plans.store") }}';

        $.ajax({
            url: url,
            method: method,
            data: $(this).serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                toastr.success(response.message);
                $('#planModal').modal('hide');
                table.ajax.reload();
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error saving plan');
            }
        });
    });

    // Status toggle
    $('#plansTable').on('change', '.status-toggle', function() {
        const id = $(this).data('id');
        const status = $(this).prop('checked') ? 1 : 0;
        changeStatus(`/admin/subscription-plans/${id}/status`, !status, 'plan', function() {
            table.ajax.reload();
        });
    });

    // Delete button
    $('#plansTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        deleteRecord(`/admin/subscription-plans/${id}`, 'plan', function() {
            table.ajax.reload();
        });
    });
});
</script>
@endpush