@extends('layouts.app')

@section('title', 'Add Item')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0 d-flex align-items-center gap-2">
            <span id="pageTitleText">Add Item</span>
            <span id="typeBadge" class="badge bg-primary d-none">Goods</span>
        </h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.items.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

<!-- Item Type Selection Modal -->
<div class="modal fade" id="itemTypeModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Select Item Type</h5>
            </div>
            <div class="modal-body text-center">
                <p class="mb-4">What would you like to create?</p>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-outline-primary btn-lg" id="itemTypeGoods" style="width: 150px;">
                        <i class="bi bi-box me-2"></i><br>Goods
                    </button>
                    <button type="button" class="btn btn-outline-info btn-lg" id="itemTypeService" style="width: 150px;">
                        <i class="bi bi-briefcase me-2"></i><br>Service
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="itemForm">
            <input type="hidden" id="type" name="type" value="goods">
            <input type="hidden" id="is_stockable" name="is_stockable" value="1">

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="item_code" class="form-label">Item Code <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="item_code" name="item_code" required placeholder="e.g. ITEM-001">
                </div>
                <div class="col-md-4">
                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required>
                </div>
                <div class="col-md-4">
                    <label for="category_id" class="form-label">Category</label>
                    <select class="form-select" id="category_id" name="category_id">
                        <option value="">Select Category</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="hsn_sac_code" class="form-label" id="hsnSacLabel">HSN/SAC Code</label>
                    <input type="text" class="form-control" id="hsn_sac_code" name="hsn_sac_code">
                </div>
                <div class="col-md-4">
                    <label for="tax_rate_id" class="form-label">Tax Rate</label>
                    <select class="form-select" id="tax_rate_id" name="tax_rate_id">
                        <option value="">Select Tax Rate</option>
                        @foreach($taxRates as $taxRate)
                        <option value="{{ $taxRate->id }}">{{ $taxRate->name }} ({{ $taxRate->tax_rate }}%)</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="unit" class="form-label">Unit</label>
                    <select class="form-select" id="unit" name="unit">
                        <option value="nos">Numbers (Nos)</option>
                        <option value="hrs">Hours (Hrs)</option>
                        <option value="kg">Kilogram (Kg)</option>
                        <option value="ltr">Litre (Ltr)</option>
                        <option value="mtr">Metre (Mtr)</option>
                        <option value="pcs">Pieces (Pcs)</option>
                        <option value="box">Box</option>
                        <option value="set">Set</option>
                    </select>
                </div>
                <div class="col-md-4" id="purchasePriceField">
                    <label for="purchase_price" class="form-label">Purchase Price</label>
                    <input type="number" class="form-control" id="purchase_price" name="purchase_price" step="0.01" min="0" value="0">
                </div>
                <div class="col-md-4">
                    <label for="selling_price" class="form-label" id="sellingPriceLabel">Selling Price</label>
                    <input type="number" class="form-control" id="selling_price" name="selling_price" step="0.01" min="0" value="0">
                    <div class="form-text" id="sellingPriceHint" style="display:none;">Optional default rate for sales invoice; amount can still be changed per bill.</div>
                </div>
                <div class="col-md-4" id="barcodeField">
                    <label for="barcode" class="form-label">Barcode</label>
                    <input type="text" class="form-control" id="barcode" name="barcode">
                </div>
                <div class="col-md-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="btn btn-primary" id="saveItemBtn">
                    <i class="bi bi-check-circle me-2"></i>Save Item
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
function applyItemTypeUi(type) {
    const isService = type === 'service';
    $('#type').val(type);
    $('#is_stockable').val(isService ? '0' : '1');
    $('#typeBadge')
        .removeClass('d-none bg-primary bg-info')
        .addClass(isService ? 'bg-info' : 'bg-primary')
        .text(isService ? 'Service' : 'Goods');
    $('#pageTitleText').text(isService ? 'Add Service' : 'Add Item');
    $('#hsnSacLabel').text(isService ? 'SAC Code' : 'HSN/SAC Code');
    $('#barcodeField').toggle(!isService);
    $('#purchasePriceField').toggle(!isService);
    $('#sellingPriceLabel').text(isService ? 'Default Rate' : 'Selling Price');
    $('#sellingPriceHint').toggle(isService);
    if (isService) {
        $('#barcode').val('');
        $('#purchase_price').val('0');
        const unit = $('#unit');
        if (!['hrs', 'nos'].includes(unit.val())) {
            unit.val('hrs');
        }
        $('#saveItemBtn').html('<i class="bi bi-check-circle me-2"></i>Save Service');
    } else {
        $('#saveItemBtn').html('<i class="bi bi-check-circle me-2"></i>Save Item');
    }
}

$(document).ready(function() {
    $('#itemTypeModal').modal('show');

    $('#itemTypeGoods').click(function() {
        applyItemTypeUi('goods');
        $('#itemTypeModal').modal('hide');
    });

    $('#itemTypeService').click(function() {
        applyItemTypeUi('service');
        $('#itemTypeModal').modal('hide');
    });

    ajaxFormSubmit('#itemForm', '{{ route("admin.items.store") }}', 'POST', '{{ route("admin.items.index") }}');
});
</script>
@endsection
