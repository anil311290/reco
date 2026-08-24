@extends('layouts.app')

@section('title', 'Stock Value Register')

@include('admin.reports._theme')

@section('content')
<div class="reports-shell">
    <div class="report-hero">
        <div class="row g-4 align-items-center">
            <div class="col-lg-8">
                <span class="report-eyebrow"><i class="bi bi-cash-stack"></i> Profit &amp; Loss</span>
                <h1 class="report-title">Stock Value Register</h1>
                <p class="report-subtitle">Enter the total stock value by date. The latest values are used in the Profit &amp; Loss report.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="{{ route('admin.reports.profit-loss', ['financial_year_id' => $financialYearId]) }}" class="btn report-btn-soft">
                    <i class="bi bi-arrow-left me-1"></i>Back to Profit &amp; Loss
                </a>
            </div>
        </div>
    </div>

    <div class="report-filter-card">
        <div class="report-filter-head">
            <span class="report-filter-head-title"><i class="bi bi-funnel"></i> Filters</span>
            <a href="{{ route('admin.reports.stock-value-register') }}" class="report-filter-reset"><i class="bi bi-arrow-counterclockwise"></i> Reset</a>
        </div>
        <form method="GET" action="{{ route('admin.reports.stock-value-register') }}" class="row g-3 align-items-end">
            <div class="col-12 col-lg-4">
                <label for="financial_year_id" class="form-label">Financial Year</label>
                <select id="financial_year_id" name="financial_year_id" class="form-select" required>
                    @foreach($financialYears as $financialYear)
                        <option value="{{ $financialYear->id }}" {{ (int) $financialYearId === (int) $financialYear->id ? 'selected' : '' }}>{{ $financialYear->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label for="from_date" class="form-label">From Date</label>
                <input type="date" id="from_date" name="from_date" class="form-control" value="{{ request('from_date') }}">
            </div>
            <div class="col-12 col-md-6 col-lg-2">
                <label for="to_date" class="form-label">To Date</label>
                <input type="date" id="to_date" name="to_date" class="form-control" value="{{ request('to_date') }}">
            </div>
            <div class="col-12 col-lg-auto report-filter-actions">
                <button type="submit" class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
            </div>
        </form>
    </div>

    <div class="report-panel">
        <div class="report-panel-header">
            <div>
                <h5 class="report-panel-title"><i class="bi bi-calendar3 text-primary"></i>Stock Value Entries</h5>
                <p class="mb-0 text-muted">Create or edit a dated total value. Entries cannot be deleted.</p>
            </div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stockValueEntryModal" data-entry-id="" data-entry-date="" data-entry-value="" data-entry-remarks="">
                <i class="bi bi-plus-circle me-1"></i>Add Entry
            </button>
        </div>
        <div class="table-responsive">
            <table class="table report-table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th class="text-end">Stock Value (₹)</th>
                        <th>Remarks</th>
                        <th>Updated</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td class="fw-semibold">{{ $entry->valuation_date->format('d/m/Y') }}</td>
                            <td class="text-end fw-bold">₹{{ number_format((float) $entry->stock_value, 2) }}</td>
                            <td>{{ $entry->remarks ?: '-' }}</td>
                            <td>{{ $entry->updated_at?->format('d/m/Y H:i') ?: '-' }}</td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#stockValueEntryModal"
                                        data-entry-id="{{ $entry->id }}" data-entry-date="{{ $entry->valuation_date->format('Y-m-d') }}"
                                        data-entry-value="{{ $entry->stock_value }}" data-entry-remarks="{{ $entry->remarks }}">
                                    <i class="bi bi-pencil-square me-1"></i>Edit
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-5">No stock value entries found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="stockValueEntryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="stockValueEntryForm" method="POST" action="{{ route('admin.reports.stock-value-register.store') }}">
                @csrf
                <input type="hidden" name="financial_year_id" value="{{ $financialYearId }}">
                <input type="hidden" id="stockValueEntryMethod" name="_method" value="POST">
                <div class="modal-header">
                    <h5 class="modal-title" id="stockValueEntryModalTitle">Add Stock Value</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="valuation_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="valuation_date" name="valuation_date" required>
                    </div>
                    <div class="mb-3">
                        <label for="stock_value" class="form-label">Stock Value (₹)</label>
                        <input type="number" class="form-control" id="stock_value" name="stock_value" min="0" step="0.01" required>
                    </div>
                    <div>
                        <label for="remarks" class="form-label">Remarks</label>
                        <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(function () {
        $('#stockValueEntryModal').on('show.bs.modal', function (event) {
            const button = $(event.relatedTarget);
            const entryId = button.data('entry-id') || '';
            const form = $('#stockValueEntryForm');
            $('#stockValueEntryModalTitle').text(entryId ? 'Edit Stock Value' : 'Add Stock Value');
            $('#valuation_date').val(button.data('entry-date') || '');
            $('#stock_value').val(button.data('entry-value') || '');
            $('#remarks').val(button.data('entry-remarks') || '');
            form.attr('action', entryId
                ? '{{ url('/admin/reports/stock-value-register') }}/' + entryId
                : '{{ route('admin.reports.stock-value-register.store') }}');
            $('#stockValueEntryMethod').val(entryId ? 'PUT' : 'POST');
        });

        $('#stockValueEntryForm').on('submit', function (event) {
            event.preventDefault();
            const form = $(this);
            const submitButton = form.find('button[type="submit"]');
            submitButton.prop('disabled', true);
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                success: function (response) {
                    if (!response.success) {
                        submitButton.prop('disabled', false);
                        toastr.error(response.message || 'Unable to save stock value.');
                        return;
                    }

                    const modal = bootstrap.Modal.getInstance(document.getElementById('stockValueEntryModal'));
                    modal?.hide();
                    toastr.success(response.message || 'Stock value saved successfully.');
                    setTimeout(function () {
                        window.location.reload();
                    }, 700);
                },
                error: function (xhr) {
                    submitButton.prop('disabled', false);
                    const validationErrors = xhr.responseJSON?.errors || {};
                    const firstValidationMessage = Object.values(validationErrors).flat()[0];
                    toastr.error(firstValidationMessage || xhr.responseJSON?.message || 'Unable to save stock value.');
                }
            });
        });
    });
</script>
@endpush
