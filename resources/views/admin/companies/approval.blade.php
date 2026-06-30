@extends('layouts.app')

@section('title', 'Company Approvals')

@section('content')
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-body p-4 p-lg-5" style="background: linear-gradient(135deg, #f8fafc 0%, #eef4ff 100%);">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge rounded-pill text-bg-warning-subtle text-warning-emphasis mb-3">Pending Onboarding</span>
                    <h2 class="mb-2">Company Approvals</h2>
                    <p class="text-muted mb-0">Approve verified businesses quickly or reject incomplete signups before they enter the platform.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-primary">
                        <i class="bi bi-buildings me-2"></i>All Companies
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 py-3">
            <h5 class="mb-1">Pending Requests</h5>
            <p class="text-muted small mb-0">Each action updates the tenant onboarding state immediately.</p>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="approvalTable" width="100%">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Company</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Requested</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(function () {
    const table = loadDatatable('approvalTable', '{{ route("admin.companies.approval") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'name', name: 'name' },
        { data: 'owner_name', name: 'users.name', defaultContent: 'N/A' },
        { data: 'owner_email', name: 'users.email', defaultContent: 'N/A' },
        { data: 'created_at', name: 'created_at' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
    ], {
        order: [[4, 'desc']]
    });

    function performCompanyAction(url, label, successMessage) {
        $.ajax({
            url: url,
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function (response) {
                toastr.success(response.message || successMessage);
                table.ajax.reload(null, false);
            },
            error: function (xhr) {
                ajaxErrorHandler(xhr, 'Unable to ' + label + ' company.');
            }
        });
    }

    $(document).on('click', '.js-company-approve', function () {
        const url = $(this).data('url');
        const name = $(this).data('name') || 'this company';

        Swal.fire({
            title: 'Approve company?',
            text: 'This will activate ' + name + ' and allow its users to access the platform.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Approve',
            confirmButtonColor: '#198754'
        }).then((result) => {
            if (result.isConfirmed) {
                performCompanyAction(url, 'approve', name + ' approved successfully.');
            }
        });
    });

    $(document).on('click', '.js-company-reject', function () {
        const url = $(this).data('url');
        const name = $(this).data('name') || 'this company';

        Swal.fire({
            title: 'Reject company?',
            text: 'This will cancel onboarding for ' + name + ' and remove its pending users.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Reject',
            confirmButtonColor: '#dc3545'
        }).then((result) => {
            if (result.isConfirmed) {
                performCompanyAction(url, 'reject', name + ' rejected successfully.');
            }
        });
    });
});
</script>
@endsection