@extends('layouts.app')

@section('title', 'Financial Years')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Financial Years</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @permission('financial-years.create')
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createFinancialYearModal">
            <i class="bi bi-plus-circle me-2"></i>Add Financial Year
        </button>
        @endpermission
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="financialYearsTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Current</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Financial Year Modal -->
<div class="modal fade" id="createFinancialYearModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Financial Year</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="createFinancialYearForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" 
                               placeholder="e.g., 2025-2026" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        <small>Financial year typically runs from April 1 to March 31.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Create
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Load financial years
    function loadFinancialYears() {
        $.ajax({
            url: '{{ route("admin.financial-years.index") }}',
            method: 'GET',
            success: function(response) {
                const tbody = $('#financialYearsTable tbody');
                tbody.empty();

                if (response.data.length === 0) {
                    tbody.append(`
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="bi bi-calendar3 fs-1 text-muted d-block mb-2"></i>
                                No financial years found. Create your first financial year.
                            </td>
                        </tr>
                    `);
                    return;
                }

                response.data.forEach(function(fy, index) {
                    const statusBadge = fy.is_closed 
                        ? '<span class="badge bg-secondary">Closed</span>'
                        : '<span class="badge bg-success">Open</span>';
                    
                    const currentBadge = fy.is_current 
                        ? '<span class="badge bg-primary">Current</span>'
                        : '';

                    let actions = '';
                    if (!fy.is_current && !fy.is_closed) {
                        actions += `
                            <button class="btn btn-sm btn-outline-primary set-current-btn" data-id="${fy.id}" title="Set as Current">
                                <i class="bi bi-check-circle"></i>
                            </button>
                        `;
                    }
                    if (!fy.is_closed) {
                        actions += `
                            <button class="btn btn-sm btn-outline-warning close-btn" data-id="${fy.id}" title="Close Year">
                                <i class="bi bi-lock"></i>
                            </button>
                        `;
                    }
                    if (!fy.is_current) {
                        actions += `
                            <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${fy.id}" title="Delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        `;
                    }

                    tbody.append(`
                        <tr>
                            <td>${index + 1}</td>
                            <td><strong>${fy.name}</strong></td>
                            <td>${formatDateIst(fy.start_date)}</td>
                            <td>${formatDateIst(fy.end_date)}</td>
                            <td>${statusBadge}</td>
                            <td>${currentBadge}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    ${actions}
                                </div>
                            </td>
                        </tr>
                    `);
                });

                // Bind events
                bindEvents();
            }
        });
    }

    // Bind events
    function bindEvents() {
        // Set as current
        $('.set-current-btn').on('click', function() {
            const id = $(this).data('id');
            
            Swal.fire({
                title: 'Set as Current?',
                text: 'This will make this financial year the active year.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                confirmButtonText: 'Yes, set as current'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/financial-years/${id}/set-current`,
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                loadFinancialYears();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'An error occurred');
                        }
                    });
                }
            });
        });

        // Close year
        $('.close-btn').on('click', function() {
            const id = $(this).data('id');
            
            Swal.fire({
                title: 'Close Financial Year?',
                text: 'This action cannot be undone. You won\'t be able to create new entries for this year.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Yes, close it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/financial-years/${id}/close`,
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                loadFinancialYears();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'An error occurred');
                        }
                    });
                }
            });
        });

        // Delete
        $('.delete-btn').on('click', function() {
            const id = $(this).data('id');
            
            Swal.fire({
                title: 'Delete Financial Year?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Yes, delete it'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/financial-years/${id}`,
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                toastr.success(response.message);
                                loadFinancialYears();
                            } else {
                                toastr.error(response.message);
                            }
                        },
                        error: function(xhr) {
                            toastr.error(xhr.responseJSON?.message || 'An error occurred');
                        }
                    });
                }
            });
        });
    }

    // Create form submission
    $('#createFinancialYearForm').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Creating...');
        
        $.ajax({
            url: '{{ route("admin.financial-years.store") }}',
            method: 'POST',
            data: form.serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#createFinancialYearModal').modal('hide');
                    form[0].reset();
                    loadFinancialYears();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(key) {
                        toastr.error(errors[key][0]);
                    });
                } else {
                    toastr.error(xhr.responseJSON?.message || 'An error occurred');
                }
            },
            complete: function() {
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Auto-generate name from dates
    $('#start_date, #end_date').on('change', function() {
        const startDate = $('#start_date').val();
        const endDate = $('#end_date').val();
        
        if (startDate && endDate) {
            const startYear = new Date(startDate).getFullYear();
            const endYear = new Date(endDate).getFullYear();
            $('#name').val(`${startYear}-${endYear}`);
        }
    });

    // Load initial data
    loadFinancialYears();
});
</script>
@endpush
