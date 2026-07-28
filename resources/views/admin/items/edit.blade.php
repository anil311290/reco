@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
@php
    $isService = ($item->type ?? 'goods') === 'service';
@endphp
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0 d-flex align-items-center gap-2">
            <span>Edit {{ $isService ? 'Service' : 'Item' }}</span>
            <span class="badge {{ $isService ? 'bg-info' : 'bg-primary' }}">{{ $isService ? 'Service' : 'Goods' }}</span>
        </h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

@if($isService)
<div class="alert alert-info py-2">
    Stock does not apply to service items. They are non-stockable and post to Sales Revenue via income account.
</div>
@endif

<div class="card">
    <div class="card-body">
        <form id="itemForm">
            @method('PUT')
            <input type="hidden" name="type" value="{{ $item->type ?? 'goods' }}">
            <input type="hidden" name="is_stockable" value="{{ $isService ? '0' : '1' }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="item_code" class="form-label">Item Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="item_code" name="item_code" value="{{ $item->item_code }}" required>
                </div>
                <div class="col-md-4">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" value="{{ $item->name }}" required>
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ $item->category_id == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="hsn_sac_code" class="form-label">{{ $isService ? 'SAC Code' : 'HSN/SAC Code' }}</label>
                    <input type="text" class="form-control" id="hsn_sac_code" name="hsn_sac_code" value="{{ $item->hsn_sac_code }}">
                </div>
                <div class="col-md-4">
                    <label for="tax_rate_id" class="form-label">Tax Rate</label>
                    <select class="form-select" id="tax_rate_id" name="tax_rate_id">
                        <option value="">Select Tax Rate</option>
                        @foreach($taxRates as $taxRate)
                        <option value="{{ $taxRate->id }}" {{ $item->tax_rate_id == $taxRate->id ? 'selected' : '' }}>{{ $taxRate->name }} ({{ $taxRate->rate }}%)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="unit" class="form-label">Unit</label>
                    <select class="form-select" id="unit" name="unit">
                        <option value="nos" {{ $item->unit == 'nos' ? 'selected' : '' }}>Numbers (Nos)</option>
                        <option value="hrs" {{ $item->unit == 'hrs' ? 'selected' : '' }}>Hours (Hrs)</option>
                        <option value="kg" {{ $item->unit == 'kg' ? 'selected' : '' }}>Kilogram (Kg)</option>
                        <option value="ltr" {{ $item->unit == 'ltr' ? 'selected' : '' }}>Litre (Ltr)</option>
                        <option value="mtr" {{ $item->unit == 'mtr' ? 'selected' : '' }}>Metre (Mtr)</option>
                        <option value="pcs" {{ $item->unit == 'pcs' ? 'selected' : '' }}>Pieces (Pcs)</option>
                    </select>
                </div>
                @unless($isService)
                <div class="col-md-4">
                    <label for="purchase_price" class="form-label">Purchase Price</label>
                    <input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" min="0" value="{{ $item->purchase_price }}">
                </div>
                @else
                <input type="hidden" name="purchase_price" value="0">
                @endunless
                <div class="col-md-4">
                    <label for="selling_price" class="form-label">{{ $isService ? 'Default Rate' : 'Selling Price' }}</label>
                    <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" min="0" value="{{ $item->selling_price }}">
                    @if($isService)
                    <div class="form-text">Optional default rate for sales invoice; amount can still be changed per bill.</div>
                    @endif
                </div>
                @unless($isService)
                <div class="col-md-4">
                    <label for="barcode" class="form-label">Barcode</label>
                    <input type="text" class="form-control" id="barcode" name="barcode" value="{{ $item->barcode }}">
                </div>
                @endunless
                <div class="col-md-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2">{{ $item->description }}</textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-circle me-2"></i>Update {{ $isService ? 'Service' : 'Item' }}
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
ajaxFormSubmit('#itemForm', '{{ route("admin.items.update", $item->id) }}', 'PUT', '{{ route("admin.items.index") }}');
</script>
@endsection
