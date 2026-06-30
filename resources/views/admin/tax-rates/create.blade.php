@extends('layouts.app')

@section('title', 'Add Tax Rate')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Create Tax Master</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.tax-rates.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="taxRateForm">
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
                <div class="col-md-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Additional notes..."></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Save Tax Rate
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
ajaxFormSubmit('#taxRateForm', '{{ route("admin.tax-rates.store") }}', 'POST', '{{ route("admin.tax-rates.index") }}');
</script>
@endsection
