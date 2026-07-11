@extends('layouts.app')

@section('title', 'Tax Rates')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Tax Master</h4>
    </div>
    <div class="col-md-6 text-md-end">
        @permission('tax-rates.create')
        <button type="button" class="btn btn-primary" id="addTaxRateBtn">
            <i class="bi bi-plus-circle me-2"></i>Add Tax Rate
        </button>
        @endpermission
    </div>
</div>

{{-- Create / Edit Modal --}}
<div class="modal fade" id="taxRateModal" tabindex="-1" aria-labelledby="taxRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="taxRateModalLabel">Add Tax Rate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="taxRateForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="tax_name" class="form-label">Tax Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="tax_name" name="tax_name" required placeholder="e.g. GST 18%">
                        </div>
                        <div class="col-md-6">
                            <label for="tax_code" class="form-label">Tax Code</label>
                            <input type="text" class="form-control" id="tax_code" name="tax_code" placeholder="e.g. GST18">
                        </div>
                        <div class="col-md-4">
                            <label for="tax_rate" class="form-label">Tax Rate (%) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" required>
                        </div>
                        <div class="col-md-4">
                            <label for="tax_type" class="form-label">Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="tax_type" name="tax_type" required>
                                <option value="addition">Addition</option>
                                <option value="deduction">Deduction</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tax_category" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="tax_category" name="tax_category" required>
                                <option value="GST">GST</option>
                                <option value="CGST">CGST</option>
                                <option value="SGST">SGST</option>
                                <option value="IGST">IGST</option>
                                <option value="TDS">TDS</option>
                                <option value="TCS">TCS</option>
                                <option value="CESS">CESS</option>
                                <option value="OTHER">OTHER</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveTaxRateBtn">
                        <i class="bi bi-check-circle me-2"></i>Save Tax Rate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form id="filterForm" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Search by name or code...">
            </div>
            <div class="col-md-3">
                <label for="filter_tax_category" class="form-label">Category</label>
                <select class="form-select" id="filter_tax_category" name="tax_category">
                    <option value="">All Categories</option>
                    <option value="GST">GST</option>
                    <option value="CGST">CGST</option>
                    <option value="SGST">SGST</option>
                    <option value="IGST">IGST</option>
                    <option value="TDS">TDS</option>
                    <option value="TCS">TCS</option>
                    <option value="CESS">CESS</option>
                    <option value="OTHER">OTHER</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_tax_type" class="form-label">Type</label>
                <select class="form-select" id="filter_tax_type" name="tax_type">
                    <option value="">All Types</option>
                    <option value="addition">Addition</option>
                    <option value="deduction">Deduction</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="filter_status" class="form-label">Status</label>
                <select class="form-select" id="filter_status" name="status">
                    <option value="">All</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary btn-sm px-4">
                    <i class="bi bi-search me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="taxRatesTable" class="table table-hover align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tax Code</th>
                        <th>Tax Name</th>
                        <th>Category</th>
                        <th>Type</th>
                        <th>Rate (%)</th>
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
    const table = loadDatatable('taxRatesTable', '{{ route("admin.tax-rates.index") }}', [
        { data: null, name: 'serial', orderable: false, searchable: false, render: function(data, type, row, meta) { return meta.row + meta.settings._iDisplayStart + 1; } },
        { data: 'tax_code', name: 'tax_code' },
        { data: 'tax_name', name: 'tax_name' },
        { 
            data: 'tax_category',
            name: 'tax_category',
            render: function(data) {
                return data || '-';
            }
        },
        { 
            data: 'tax_type',
            name: 'tax_type',
            render: function(data) {
                return data ? '<span class="badge bg-info">' + (data.charAt(0).toUpperCase() + data.slice(1)) + '</span>' : '<span class="text-muted">-</span>';
            }
        },
        { data: 'tax_rate', name: 'tax_rate' },
        { 
            data: 'status',
            name: 'status',
            render: function(data, type, row) {
                const isActive = data === 'active';
                const checked = isActive ? 'checked' : '';
                return `
                    <div class="form-check form-switch">
                        <input class="form-check-input status-toggle" type="checkbox" 
                               data-id="${row.id}" ${checked}>
                        <label class="form-check-label">
                            ${isActive ? 'Active' : 'Inactive'}
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
                @permission('tax-rates.edit')
                actions += `<button class="btn btn-outline-primary edit-btn"
                    data-id="${data.id}"
                    data-tax_name="${data.tax_name}"
                    data-tax_code="${data.tax_code || ''}"
                    data-tax_rate="${data.tax_rate}"
                    data-tax_type="${data.tax_type || 'addition'}"
                    data-tax_category="${data.tax_category || 'GST'}"
                    data-status="${data.status || 'active'}"
                    data-notes="${(data.notes || '').replace(/"/g, '&quot;')}"
                    title="Edit"><i class="bi bi-pencil"></i></button>`;
                @endpermission
                @permission('tax-rates.delete')
                actions += `<button class="btn btn-outline-danger delete-btn" data-id="${data.id}" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>`;
                @endpermission
                actions += `</div>`;
                return actions;
            }
        }
    ], {
        order: [[1, 'asc']],
        pageLength: 25,
        ajax: {
            data: function(d) {
                d.search = $('#search').val();
                d.tax_category = $('#filter_tax_category').val();
                d.tax_type = $('#filter_tax_type').val();
                d.status = $('#filter_status').val();
            }
        }
    });

    // Filter form submission
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        table.ajax.reload();
    });

    // Status toggle
    $('#taxRatesTable').on('change', '.status-toggle', function() {
        const id = $(this).data('id');
        const status = $(this).prop('checked') ? 1 : 0;
        changeStatus(`/admin/tax-rates/${id}/status`, !status, 'tax rate', function() {
            table.ajax.reload();
        });
    });

    // Delete button
    $('#taxRatesTable').on('click', '.delete-btn', function() {
        const id = $(this).data('id');
        deleteRecord(`/admin/tax-rates/${id}`, 'tax rate', function() {
            table.ajax.reload();
        });
    });

    // Open modal for Create
    $('#addTaxRateBtn').on('click', function() {
        $('#taxRateModalLabel').text('Add Tax Rate');
        $('#saveTaxRateBtn').html('<i class="bi bi-check-circle me-2"></i>Save Tax Rate');
        $('#taxRateForm')[0].reset();
        $('#tax_type').val('addition');
        $('#tax_category').val('GST');
        $('#status').val('active');
        ajaxFormSubmit('#taxRateForm', '{{ route("admin.tax-rates.store") }}', 'POST', function() {
            $('#taxRateModal').modal('hide');
            table.ajax.reload();
        });
        $('#taxRateModal').modal('show');
    });

    // Open modal for Edit
    $('#taxRatesTable').on('click', '.edit-btn', function() {
        const btn = $(this);
        const id = btn.data('id');
        $('#taxRateModalLabel').text('Edit Tax Rate');
        $('#saveTaxRateBtn').html('<i class="bi bi-check-circle me-2"></i>Update Tax Rate');
        $('#taxRateForm')[0].reset();
        $('#tax_name').val(btn.data('tax_name'));
        $('#tax_code').val(btn.data('tax_code'));
        $('#tax_rate').val(btn.data('tax_rate'));
        $('#tax_type').val(btn.data('tax_type'));
        $('#tax_category').val(btn.data('tax_category'));
        $('#status').val(btn.data('status'));
        $('#notes').val(btn.data('notes'));
        ajaxFormSubmit('#taxRateForm', `/admin/tax-rates/${id}`, 'PUT', function() {
            $('#taxRateModal').modal('hide');
            table.ajax.reload();
        });
        $('#taxRateModal').modal('show');
    });
});
</script>
@endpush