@extends('layouts.app')

@section('title', 'All Companies')

@section('content')
<div class="container-fluid px-0">
    <div class="card border-0 shadow-sm overflow-hidden mb-4">
        <div class="card-body p-4 p-lg-5 bg-light bg-gradient">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <span class="badge rounded-pill text-bg-primary-subtle text-primary mb-3">Platform Control</span>
                    <h2 class="mb-2">All Companies</h2>
                    <p class="text-muted mb-0">Manage tenant businesses from a cleaner control center. Review company profile details, update contact data, and retire inactive tenants from one place.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="{{ route('admin.companies.approval') }}" class="btn btn-primary">
                        <i class="bi bi-building-check me-2"></i>Review Approvals
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 py-3">
            <div>
                <h5 class="mb-1">Tenant Directory</h5>
                <p class="text-muted small mb-0">Edit tenant records or remove unused companies.</p>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table align-middle" id="companiesTable" width="100%">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Company</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Created</th>
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
    const table = loadDatatable('companiesTable', '{{ route("admin.companies.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'name', name: 'name' },
        { data: 'email', name: 'email', defaultContent: '-' },
        { data: 'phone', name: 'phone', defaultContent: '-' },
        { data: 'status_badge', name: 'is_active', orderable: false, searchable: false },
        { data: 'created_at', name: 'created_at' },
        { data: 'actions', name: 'actions', orderable: false, searchable: false, className: 'text-end' }
    ], {
        order: [[5, 'desc']]
    });

    $(document).on('click', '.js-company-delete', function () {
        deleteRecord($(this).data('url'), $(this).data('name') || 'company', function () {
            table.ajax.reload(null, false);
        });
    });
});
</script>
@endsection
