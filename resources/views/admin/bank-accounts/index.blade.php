@extends('layouts.app')

@section('title', 'Bank Accounts')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Bank Accounts</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @permission('accounts.create')
        <a href="{{ route('admin.bank-accounts.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-2"></i>Add Bank Account
        </a>
        @endpermission
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="bankAccountsTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Bank Name</th>
                        <th>Account Number</th>
                        <th>IFSC</th>
                        <th>Type</th>
                        <th>Holder Name</th>
                        <th>Default</th>
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
    const table = loadDatatable('bankAccountsTable', '{{ route("admin.bank-accounts.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'bank_name', name: 'bank_name' },
        { data: 'account_number', name: 'account_number' },
        { data: 'ifsc_code', name: 'ifsc_code' },
        { 
            data: 'account_type', 
            name: 'account_type',
            render: function(data) {
                let types = {savings:'Savings',current:'Current',fixed_deposit:'FD',cc_od:'CC/OD'};
                return types[data] || data;
            }
        },
        { data: 'account_holder_name', name: 'account_holder_name' },
        { 
            data: 'is_default', 
            name: 'is_default',
            render: function(data) {
                return data ? '<span class="badge bg-primary">Default</span>' : '<span class="text-muted">-</span>';
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
                @permission('accounts.update')
                actions += `<a href="/admin/bank-accounts/${data.id}/edit" class="btn btn-outline-primary" title="Edit">
                    <i class="bi bi-pencil"></i>
                </a>`;
                @endpermission
                if (!data.is_default) {
                    actions += `<button class="btn btn-outline-info default-btn" data-id="${data.id}" title="Set as Default">
                        <i class="bi bi-star"></i>
                    </button>`;
                }
                @permission('accounts.delete')
                actions += `<button class="btn btn-outline-danger delete-btn" data-id="${data.id}" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>`;
                @endpermission
                actions += `</div>`;
                return actions;
            }
        }
    ]);

    // Status toggle
    $('#bankAccountsTable').on('change', '.status-toggle', function() {
        const id = $(this).data('id');
        const status = $(this).prop('checked') ? 1 : 0;
        changeStatus(`/admin/bank-accounts/${id}/status`, !status, 'bank account', function() {
            table.ajax.reload();
        });
    });

    // Set default button
    $('#bankAccountsTable').on('click', '.default-btn', function() {
        const id = $(this).data('id');
        $.ajax({ 
            url: `/admin/bank-accounts/${id}/default`, 
            type: 'PATCH',
            headers: {'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')},
            success: function(r) { 
                if (r.success) {
                    toastr.success(r.message);
                    table.ajax.reload();
                } else {
                    toastr.error(r.message);
                }
            }
        });
    });

    // Delete button
    $('#bankAccountsTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        deleteRecord(`/admin/bank-accounts/${id}`, 'bank account', function() {
            table.ajax.reload();
        });
    });
});
</script>
@endpush