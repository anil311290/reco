<div class="modal fade" id="quickAddItemModal" tabindex="-1" aria-labelledby="quickAddItemModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddItemModalLabel">
                    {{ ($quickAddGoodsOnly ?? false) ? 'Quick Add Item' : 'Quick Add Item / Service' }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="quickAddItemForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="quick_item_type" class="form-label">Type <span class="text-danger">*</span></label>
                            @if($quickAddGoodsOnly ?? false)
                            <input type="hidden" id="quick_item_type" name="type" value="goods">
                            <input type="text" class="form-control" value="Goods" readonly>
                            @else
                            <select class="form-select" id="quick_item_type" name="type" required>
                                <option value="goods">Goods</option>
                                <option value="service">Service</option>
                            </select>
                            @endif
                        </div>
                        <div class="col-md-4">
                            <label for="quick_item_name" class="form-label">Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="quick_item_name" name="name" maxlength="255" required>
                        </div>
                        <div class="col-md-4">
                            <label for="quick_item_category" class="form-label">Category</label>
                            <select class="form-select" id="quick_item_category" name="category_id">
                                <option value="">Select Category</option>
                                @foreach($itemCategories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="quick_item_hsn_sac" class="form-label" id="quickItemHsnSacLabel">HSN/SAC Code</label>
                            <input type="text" class="form-control" id="quick_item_hsn_sac" name="hsn_sac_code" maxlength="20">
                        </div>
                        <div class="col-md-4" id="quickItemUnitField">
                            <label for="quick_item_unit" class="form-label">Unit</label>
                            <select class="form-select" id="quick_item_unit" name="unit">
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
                        <div class="col-md-4">
                            <label for="quick_item_tax_rate" class="form-label">Tax Rate</label>
                            <select class="form-select" id="quick_item_tax_rate" name="tax_rate_id">
                                <option value="">No Tax</option>
                                @foreach($taxRates as $tax)
                                <option value="{{ $tax->id }}">{{ $tax->tax_name ?? $tax->name }} ({{ $tax->tax_rate ?? $tax->rate }}%)</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4" id="quickItemPurchasePriceField">
                            <label for="quick_item_purchase_price" class="form-label">Purchase Price</label>
                            <input type="number" class="form-control" id="quick_item_purchase_price" name="purchase_price" value="0" min="0" step="0.01">
                        </div>
                        <div class="col-md-4">
                            <label for="quick_item_selling_price" class="form-label" id="quickItemSellingPriceLabel">Selling Price</label>
                            <input type="number" class="form-control" id="quick_item_selling_price" name="selling_price" value="0" min="0" step="0.01">
                        </div>
                        <div class="col-md-4" id="quickItemOpeningStockField">
                            <label for="quick_item_opening_stock" class="form-label">Opening Qty.</label>
                            <input type="number" class="form-control" id="quick_item_opening_stock" name="opening_stock" value="0" min="0" step="0.01">
                        </div>
                        <div class="col-md-4" id="quickItemBarcodeField">
                            <label for="quick_item_barcode" class="form-label">Barcode</label>
                            <input type="text" class="form-control" id="quick_item_barcode" name="barcode" maxlength="100">
                        </div>
                        <div class="col-12">
                            <label for="quick_item_description" class="form-label">Description</label>
                            <textarea class="form-control" id="quick_item_description" name="description" rows="2" maxlength="1000"></textarea>
                        </div>
                    </div>
                    <input type="hidden" id="quick_item_stockable" name="is_stockable" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-1"></i>Add and Select
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
