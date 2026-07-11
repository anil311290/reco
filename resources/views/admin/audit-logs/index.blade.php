@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Audit Logs</h4>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="mb-0">{{ $statistics['total_logs'] }}</h3>
                <small class="text-muted">Total Logs</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="mb-0 text-primary">{{ $statistics['today_logs'] }}</h3>
                <small class="text-muted">Today's Logs</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="mb-0 text-success">{{ $statistics['month_logs'] }}</h3>
                <small class="text-muted">This Month</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h3 class="mb-0 text-info">{{ count($statistics['by_module'] ?? []) }}</h3>
                <small class="text-muted">Modules Tracked</small>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search...">
            </div>
            <div class="col-md-2">
                <label for="action" class="form-label">Action</label>
                <select class="form-select" id="action" name="action">
                    <option value="">All Actions</option>
                    <option value="create">Create</option>
                    <option value="update">Update</option>
                    <option value="delete">Delete</option>
                    <option value="login">Login</option>
                    <option value="logout">Logout</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="module" class="form-label">Module</label>
                <select class="form-select" id="module" name="module">
                    <option value="">All Modules</option>
                    <option value="users">Users</option>
                    <option value="accounts">Accounts</option>
                    <option value="parties">Parties</option>
                    <option value="vouchers">Vouchers</option>
                    <option value="settings">Settings</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="user_id" class="form-label">User</label>
                <select class="form-select" id="user_id" name="user_id">
                    <option value="">All Users</option>
                    <!-- Users will be loaded dynamically -->
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
            <table id="auditLogsTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date/Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Module</th>
                        <th>Amount</th>
                        <th>Description</th>
                        <th>IP Address</th>
                        <th>Details</th>
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
    const table = loadDatatable('auditLogsTable', '{{ route("admin.audit-logs.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { 
            data: 'created_at',
            name: 'created_at',
            render: function(data) {
                return formatDateTimeIst(data);
            }
        },
        { 
            data: 'user',
            name: 'user.name',
            render: function(data) {
                return data ? data.name : '<span class="text-muted">System</span>';
            }
        },
        { 
            data: 'action',
            name: 'action',
            render: function(data) {
                const badges = {
                    'create': 'bg-success',
                    'update': 'bg-primary',
                    'delete': 'bg-danger',
                    'login': 'bg-info',
                    'logout': 'bg-secondary',
                    'status_change': 'bg-warning'
                };
                const labels = {
                    'create': 'Created',
                    'update': 'Updated',
                    'delete': 'Deleted',
                    'login': 'Logged In',
                    'logout': 'Logged Out',
                    'status_change': 'Status Changed'
                };
                return `<span class="badge ${badges[data] || 'bg-secondary'}">${labels[data] || data}</span>`;
            }
        },
        { data: 'module', name: 'module' },
        {
            data: null,
            name: 'amount',
            render: function(data) {
                const amount = data.new_values?.opening_balance ?? data.old_values?.opening_balance ?? null;
                if (amount === null || amount === undefined) {
                    return '-';
                }
                return formatCurrency(amount);
            }
        },
        { 
            data: 'description',
            name: 'description',
            render: function(data) {
                return data ? (data.length > 50 ? data.substring(0, 50) + '...' : data) : '-';
            }
        },
        { data: 'ip_address', name: 'ip_address' },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data) {
                return `
                    <a href="/admin/audit-logs/${data.id}" class="btn btn-sm btn-outline-info" title="View Details">
                        <i class="bi bi-eye"></i>
                    </a>
                `;
            }
        }
    ], {
        ajax: {
            data: function(d) {
                d.search = $('#search').val();
                d.action = $('#action').val();
                d.module = $('#module').val();
                d.user_id = $('#user_id').val();
            }
        }
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });
});
</script>
@endpush
