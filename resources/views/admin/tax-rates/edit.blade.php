@extends('layouts.app')

@section('title', 'Edit Tax Rate')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Edit Tax Master</h4>
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
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="tax_name" class="form-label">Tax Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="tax_name" name="tax_name" value="{{ $taxRate->tax_name }}" required>
                </div>
                <div class="col-md-6">
                    <label for="tax_code" class="form-label">Tax Code</label>
                    <input type="text" class="form-control" id="tax_code" name="tax_code" value="{{ $taxRate->tax_code }}">
                </div>
                <div class="col-md-4">
                    <label for="tax_rate" class="form-label">Tax Rate (%) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" value="{{ $taxRate->tax_rate }}" required>
                </div>
                <div class="col-md-4">
                    <label for="tax_type" class="form-label">Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="tax_type" name="tax_type" required>
                        <option value="addition" {{ $taxRate->tax_type == 'addition' ? 'selected' : '' }}>Addition</option>
                        <option value="deduction" {{ $taxRate->tax_type == 'deduction' ? 'selected' : '' }}>Deduction</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="tax_category" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select" id="tax_category" name="tax_category" required>
                        @foreach(['GST', 'CGST', 'SGST', 'IGST', 'TDS', 'TCS', 'CESS', 'OTHER'] as $category)
                        <option value="{{ $category }}" {{ $taxRate->tax_category == $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                    <select class="form-select" id="status" name="status" required>
                        <option value="active" {{ $taxRate->status == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ $taxRate->status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-12">
                    <label for="notes" class="form-label">Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Additional notes...">{{ $taxRate->notes }}</textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update Tax Rate
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
ajaxFormSubmit('#taxRateForm', '{{ route("admin.tax-rates.update", $taxRate->id) }}', 'PUT', '{{ route("admin.tax-rates.index") }}');
</script>
@endsection
