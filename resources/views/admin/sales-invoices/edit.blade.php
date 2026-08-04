@extends('layouts.app')

@section('title', 'Edit Sales Invoice')

@section('content')
@php
    $allLines = $invoice->lines->sortBy('sort_order')->values();
    $customerPartyOptions = collect($partyOptions['parties'] ?? [])->where('type', 'debtor')->values();
    $supplierPartyOptions = collect($partyOptions['parties'] ?? [])->where('type', 'creditor')->values();
    $legacyServiceAccounts = $allLines
        ->filter(fn ($line) => ($line->line_type ?? '') === 'service' && empty($line->item_id) && !empty($line->account_id))
        ->mapWithKeys(function ($line) {
            $account = $line->account;
            $text = $account
                ? trim(($account->account_code ?? '') . ' - ' . ($account->account_name ?? 'Service'))
                : ('Service #' . $line->account_id);
            return [$line->account_id => $text];
        });
@endphp

<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Edit Sales Invoice #{{ $invoice->invoice_number }}</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.sales-invoices.show', $invoice->id) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Invoice
        </a>
    </div>
</div>

<form id="invoiceForm">
    @method('PUT')
    <div id="builtPayload"></div>
    <div class="row g-4">
        <div class="col-lg-9 col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Invoice Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" value="{{ $invoice->invoice_number }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1">Customer <span class="text-danger">*</span></label>
                            <select class="form-select" name="party_id" id="party_id" data-quick-add-value-mode="token" data-quick-add-in-select="1" data-quick-add-party-type="debtor" data-quick-add-target="#party_id" required>
                                <option value="">Select Customer</option>
                                <optgroup label="Quick Actions">
                                <option value="__quick_add_party">+ Quick Add Party</option>
                                <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                                </optgroup>
                                @if($customerPartyOptions->isNotEmpty())
                                <optgroup label="Customers (Parties)">
                                @foreach($customerPartyOptions as $option)
                                <option value="{{ $option['value'] }}" {{ ('party:' . $invoice->party_id) === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                @endforeach
                                </optgroup>
                                @endif
                                @if($supplierPartyOptions->isNotEmpty())
                                <optgroup label="Suppliers (Parties)">
                                @foreach($supplierPartyOptions as $option)
                                <option value="{{ $option['value'] }}" {{ ('party:' . $invoice->party_id) === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                @endforeach
                                </optgroup>
                                @endif
                                @if(!empty($partyOptions['cash_bank_od_accounts']))
                                <optgroup label="Ledger Accounts (Cash/Bank/OD)">
                                    @foreach($partyOptions['cash_bank_od_accounts'] as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference #</label>
                            <input type="text" class="form-control" name="reference_number" value="{{ $invoice->reference_number }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment/Delivery Terms</label>
                            <input type="text" class="form-control" name="payment_terms" value="{{ $invoice->payment_terms ?? '' }}" placeholder="e.g., Net 30, FOB">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2">{{ $invoice->notes }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Line Items</h5>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary" id="quickAddItem">
                            <i class="bi bi-lightning-charge me-1"></i>Quick Add Item
                        </button>
                        <button type="button" class="btn btn-sm btn-primary" id="addLine">
                            <i class="bi bi-plus-circle me-1"></i>Add Line
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="linesTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:28%">Item / Service</th>
                                    <th style="width:10%;">Qty</th>
                                    <th style="width:15%;">Unit Price</th>
                                    <th style="width:10%;">Disc %</th>
                                    <th style="width:16%;">Tax</th>
                                    <th style="width:13%">Total</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody id="linesBody">
                                @forelse($allLines as $line)
                                @php
                                    $isLegacyService = ($line->line_type ?? '') === 'service' && empty($line->item_id) && !empty($line->account_id);
                                    $selectedValue = $isLegacyService ? ('service:' . $line->account_id) : ('item:' . $line->item_id);
                                    $dataKind = $isLegacyService ? 'legacy-service' : 'item';
                                @endphp
                                <tr class="line-row" data-kind="{{ $dataKind }}">
                                    <td>
                                        <select class="form-select form-select-sm particular-select w-100" data-searchable="true" data-placeholder="Search item / service">
                                            <option value="">Select Item / Service</option>
                                            <optgroup label="Goods">
                                                @foreach($goodsItems as $item)
                                                <option value="item:{{ $item->id }}" data-kind="item" data-id="{{ $item->id }}" data-price="{{ $item->selling_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}" {{ !$isLegacyService && $line->item_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Services">
                                                @foreach($serviceItems as $item)
                                                <option value="item:{{ $item->id }}" data-kind="item" data-id="{{ $item->id }}" data-price="{{ $item->selling_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}" {{ !$isLegacyService && $line->item_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                                @endforeach
                                                @foreach($legacyServiceAccounts as $accountId => $accountText)
                                                <option value="service:{{ $accountId }}" data-kind="legacy-service" data-id="{{ $accountId }}" data-price="0" data-tax="" data-description="{{ e($accountText) }}" {{ $isLegacyService && $line->account_id == $accountId ? 'selected' : '' }}>{{ $accountText }} (legacy)</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1 bg-light description-input" value="{{ $line->description }}" placeholder="Description" readonly>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm qty-input {{ $isLegacyService ? 'bg-light' : '' }}" value="{{ $line->quantity }}" min="0.001" step="0.001" {{ $isLegacyService ? 'readonly' : '' }}></td>
                                    <td><input type="number" class="form-control form-control-sm price-input" value="{{ $line->unit_price }}" min="0" step="0.01"></td>
                                    <td><input type="number" class="form-control form-control-sm disc-input {{ $isLegacyService ? 'bg-light' : '' }}" value="{{ $isLegacyService ? 0 : ($line->discount_percentage ?? 0) }}" min="0" max="100" step="0.01" {{ $isLegacyService ? 'readonly' : '' }}></td>
                                    <td>
                                        <select class="form-select form-select-sm tax-select w-100">
                                            <option value="">No Tax</option>
                                            @foreach($taxRates as $tax)
                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->tax_rate ?? $tax->rate }}" {{ $line->tax_rate_id == $tax->id ? 'selected' : '' }}>{{ $tax->tax_name ?? $tax->name }} ({{ $tax->tax_rate ?? $tax->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm line-total" value="₹{{ number_format($line->total, 2) }}" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                @empty
                                <tr class="line-row" data-kind="">
                                    <td>
                                        <select class="form-select form-select-sm particular-select w-100" data-searchable="true" data-placeholder="Search item / service">
                                            <option value="">Select Item / Service</option>
                                            <optgroup label="Goods">
                                                @foreach($goodsItems as $item)
                                                <option value="item:{{ $item->id }}" data-kind="item" data-id="{{ $item->id }}" data-price="{{ $item->selling_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}">{{ $item->name }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Services">
                                                @foreach($serviceItems as $item)
                                                <option value="item:{{ $item->id }}" data-kind="item" data-id="{{ $item->id }}" data-price="{{ $item->selling_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}">{{ $item->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1 bg-light description-input" placeholder="Description" readonly>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm qty-input" value="1" min="0.001" step="0.001"></td>
                                    <td><input type="number" class="form-control form-control-sm price-input" value="0" min="0" step="0.01"></td>
                                    <td><input type="number" class="form-control form-control-sm disc-input" value="0" min="0" max="100" step="0.01"></td>
                                    <td>
                                        <select class="form-select form-select-sm tax-select w-100">
                                            <option value="">No Tax</option>
                                            @foreach($taxRates as $tax)
                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->tax_rate ?? $tax->rate }}">{{ $tax->tax_name ?? $tax->name }} ({{ $tax->tax_rate ?? $tax->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm line-total" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-4">
            <div class="card sticky-top" style="top:1rem">
                <div class="card-header"><h5 class="mb-0">Summary</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong id="subtotal">₹{{ number_format($invoice->subtotal, 2) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Discount:</span>
                        <span class="text-danger" id="discountAmount">-₹{{ number_format($invoice->discount_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax:</span>
                        <span id="taxAmount">₹{{ number_format($invoice->tax_amount, 2) }}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fs-5 fw-bold">Total:</span>
                        <span class="fs-5 fw-bold text-primary" id="totalAmount">₹{{ number_format($invoice->total, 2) }}</span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle me-2"></i>Update Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

@include('admin.items._quick-add-item-modal')

<template id="lineRowTemplate">
    <tr class="line-row" data-kind="">
        <td>
            <select class="form-select form-select-sm particular-select w-100" data-searchable="true" data-placeholder="Search item / service">
                <option value="">Select Item / Service</option>
                <optgroup label="Goods">
                    @foreach($goodsItems as $item)
                    <option value="item:{{ $item->id }}" data-kind="item" data-id="{{ $item->id }}" data-price="{{ $item->selling_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}">{{ $item->name }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Services">
                    @foreach($serviceItems as $item)
                    <option value="item:{{ $item->id }}" data-kind="item" data-id="{{ $item->id }}" data-price="{{ $item->selling_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}">{{ $item->name }}</option>
                    @endforeach
                </optgroup>
            </select>
            <input type="text" class="form-control form-control-sm mt-1 bg-light description-input" placeholder="Description" readonly>
        </td>
        <td><input type="number" class="form-control form-control-sm qty-input" value="1" min="0.001" step="0.001"></td>
        <td><input type="number" class="form-control form-control-sm price-input" value="0" min="0" step="0.01"></td>
        <td><input type="number" class="form-control form-control-sm disc-input" value="0" min="0" max="100" step="0.01"></td>
        <td>
            <select class="form-select form-select-sm tax-select w-100">
                <option value="">No Tax</option>
                @foreach($taxRates as $tax)
                <option value="{{ $tax->id }}" data-rate="{{ $tax->tax_rate ?? $tax->rate }}">{{ $tax->tax_name ?? $tax->name }} ({{ $tax->tax_rate ?? $tax->rate }}%)</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm line-total" readonly></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
    </tr>
</template>
@endsection

@section('scripts')
<script>
function applyParticularSelection(row) {
    const option = row.find('.particular-select option:selected');
    const kind = option.data('kind') || '';
    const description = option.attr('data-description') || '';
    const price = option.data('price') || 0;
    const taxId = option.data('tax') || '';

    row.attr('data-kind', kind);
    row.find('.description-input').val(kind ? description : '');

    if (kind === 'legacy-service') {
        row.find('.qty-input').val(1).prop('readonly', true).addClass('bg-light');
        row.find('.disc-input').val(0).prop('readonly', true).addClass('bg-light');
        row.find('.price-input').prop('readonly', false).removeClass('bg-light');
        if (!row.find('.price-input').data('touched')) {
            row.find('.price-input').val(0);
        }
    } else if (kind === 'item') {
        row.find('.qty-input').prop('readonly', false).removeClass('bg-light');
        row.find('.disc-input').prop('readonly', false).removeClass('bg-light');
        row.find('.price-input').val(price).prop('readonly', false).removeClass('bg-light');
        row.find('.tax-select').val(taxId);
    } else {
        row.find('.qty-input').val(1).prop('readonly', false).removeClass('bg-light');
        row.find('.disc-input').val(0).prop('readonly', false).removeClass('bg-light');
        row.find('.price-input').val(0).prop('readonly', false).removeClass('bg-light');
        row.find('.tax-select').val('');
    }

    calculateLineTotal(row);
}

function calculateLineTotal(row) {
    const qty = parseFloat(row.find('.qty-input').val()) || 0;
    const price = parseFloat(row.find('.price-input').val()) || 0;
    const disc = parseFloat(row.find('.disc-input').val()) || 0;
    const taxRate = parseFloat(row.find('.tax-select option:selected').data('rate')) || 0;

    const base = qty * price;
    const discAmount = base * (disc / 100);
    const afterDisc = base - discAmount;
    const tax = afterDisc * (taxRate / 100);

    row.find('.line-total').val('₹' + (afterDisc + tax).toFixed(2));
    calculateTotals();
}

function calculateTotals() {
    let subtotal = 0;
    let discount = 0;
    let taxTotal = 0;

    $('#linesBody tr').each(function() {
        if (!$(this).attr('data-kind')) return;

        const qty = parseFloat($(this).find('.qty-input').val()) || 0;
        const price = parseFloat($(this).find('.price-input').val()) || 0;
        const disc = parseFloat($(this).find('.disc-input').val()) || 0;
        const taxRate = parseFloat($(this).find('.tax-select option:selected').data('rate')) || 0;

        const base = qty * price;
        const discAmount = base * (disc / 100);
        const afterDisc = base - discAmount;
        const tax = afterDisc * (taxRate / 100);

        subtotal += afterDisc;
        discount += discAmount;
        taxTotal += tax;
    });

    $('#subtotal').text('₹' + subtotal.toFixed(2));
    $('#discountAmount').text('-₹' + discount.toFixed(2));
    $('#taxAmount').text('₹' + taxTotal.toFixed(2));
    $('#totalAmount').text('₹' + (subtotal + taxTotal).toFixed(2));
}

function appendHidden(name, value) {
    $('<input>', { type: 'hidden', name: name, value: value ?? '' }).appendTo('#builtPayload');
}

function buildSubmitPayload() {
    $('#builtPayload').empty();
    let itemIdx = 0;
    let serviceIdx = 0;
    let hasLine = false;

    $('#linesBody tr').each(function() {
        const row = $(this);
        const option = row.find('.particular-select option:selected');
        const kind = option.data('kind');
        if (!kind) return;

        hasLine = true;
        const description = row.find('.description-input').val() || '';
        const taxRateId = row.find('.tax-select').val() || '';
        const qty = row.find('.qty-input').val() || 1;
        const price = row.find('.price-input').val() || 0;
        const disc = row.find('.disc-input').val() || 0;

        if (kind === 'item') {
            appendHidden(`lines[${itemIdx}][item_id]`, option.data('id'));
            appendHidden(`lines[${itemIdx}][description]`, description);
            appendHidden(`lines[${itemIdx}][quantity]`, qty);
            appendHidden(`lines[${itemIdx}][unit_price]`, price);
            appendHidden(`lines[${itemIdx}][discount_percentage]`, disc);
            appendHidden(`lines[${itemIdx}][tax_rate_id]`, taxRateId);
            itemIdx++;
        } else if (kind === 'legacy-service') {
            appendHidden(`service_lines[${serviceIdx}][account_id]`, option.data('id'));
            appendHidden(`service_lines[${serviceIdx}][description]`, description);
            appendHidden(`service_lines[${serviceIdx}][amount]`, price);
            appendHidden(`service_lines[${serviceIdx}][tax_rate_id]`, taxRateId);
            serviceIdx++;
        }
    });

    return hasLine;
}

function addLineRow() {
    const row = $($('#lineRowTemplate').html());
    $('#linesBody').append(row);
    initSearchableSelects(row);

    return row;
}

function ensureTrailingEmptyRow(row) {
    const isLastRow = row.is($('#linesBody .line-row').last());

    if (isLastRow && row.find('.particular-select').val()) {
        addLineRow();
    }
}

function buildQuickItemOption(item) {
    return $('<option>', {
        value: `item:${item.id}`,
        text: item.name
    }).attr({
        'data-kind': 'item',
        'data-id': item.id,
        'data-price': item.selling_price || 0,
        'data-tax': item.tax_rate_id || '',
        'data-description': item.description || ''
    });
}

function appendQuickItemOption(select, item) {
    const groupLabel = item.type === 'service' ? 'Services' : 'Goods';
    $(select).find(`optgroup[label="${groupLabel}"]`).append(buildQuickItemOption(item));
}

let quickAddTargetRow = null;
const quickAddItemModalElement = document.getElementById('quickAddItemModal');
const quickAddItemModal = bootstrap.Modal.getOrCreateInstance(quickAddItemModalElement);

$(document).on('focus select2:open', '.particular-select', function() {
    quickAddTargetRow = $(this).closest('.line-row');
});

$('#quickAddItem').on('click', function() {
    if (!quickAddTargetRow || !document.body.contains(quickAddTargetRow[0])) {
        quickAddTargetRow = $('#linesBody .line-row').filter(function() {
            return !$(this).find('.particular-select').val();
        }).first();
    }

    if (!quickAddTargetRow.length) {
        quickAddTargetRow = addLineRow();
    }

    $('#quickAddItemForm')[0].reset();
    clearValidationErrors('#quickAddItemForm');
    $('#quick_item_type').val('goods').trigger('change');
    quickAddItemModal.show();
    quickAddItemModalElement.addEventListener('shown.bs.modal', function() {
        $('#quick_item_name').trigger('focus');
    }, { once: true });
});

$('#quick_item_type').on('change', function() {
    const isService = $(this).val() === 'service';
    $('#quickItemOpeningStockField').toggle(!isService);
    $('#quickItemPurchasePriceField').toggle(!isService);
    $('#quickItemBarcodeField').toggle(!isService);
    $('#quickItemUnitField').toggle(!isService);
    $('#quickItemHsnSacLabel').text(isService ? 'SAC Code' : 'HSN/SAC Code');
    $('#quickItemSellingPriceLabel').text(isService ? 'Default Rate' : 'Selling Price');
    $('#quick_item_opening_stock').val('0');
    $('#quick_item_purchase_price').val('0');
    $('#quick_item_barcode').val('');
    $('#quick_item_unit').prop('disabled', isService);
    $('#quick_item_stockable').val(isService ? '0' : '1');
});

ajaxFormSubmit(
    '#quickAddItemForm',
    '{{ route("admin.sales-invoices.quick-add-item") }}',
    'POST',
    function(response) {
        const item = response.data;

        $('.particular-select').each(function() {
            appendQuickItemOption(this, item);
        });

        const templateContent = document.getElementById('lineRowTemplate').content;
        appendQuickItemOption($(templateContent).find('.particular-select'), item);

        quickAddTargetRow.find('.particular-select')
            .val(`item:${item.id}`)
            .trigger('change');

        quickAddItemModal.hide();
        $('#quickAddItemForm')[0].reset();
        quickAddTargetRow = null;
    }
);

$('#addLine').on('click', function() {
    addLineRow();
});

$(document).on('click', '.remove-line', function() {
    if ($('#linesBody tr').length > 1) {
        $(this).closest('tr').remove();
        calculateTotals();
    }
});

$(document).on('change', '.particular-select', function() {
    const row = $(this).closest('tr');
    applyParticularSelection(row);
    ensureTrailingEmptyRow(row);
});

$(document).on('input', '.qty-input, .price-input, .disc-input', function() {
    $(this).data('touched', true);
    calculateLineTotal($(this).closest('tr'));
});

$(document).on('change', '.tax-select', function() {
    calculateLineTotal($(this).closest('tr'));
});

$('#invoiceForm').on('submit.clientValidate', function(e) {
    let hasError = false;
    $(this).find('.is-invalid').removeClass('is-invalid');
    $(this).find('.invalid-feedback').remove();

    if (!$('#party_id').val()) {
        $('#party_id').addClass('is-invalid')
            .after('<div class="invalid-feedback d-block">Please select a customer</div>');
        hasError = true;
    }

    const invoiceDate = $('[name="invoice_date"]').val();
    const dueDate = $('[name="due_date"]').val();
    if (!invoiceDate) {
        $('[name="invoice_date"]').addClass('is-invalid')
            .after('<div class="invalid-feedback d-block">Invoice date is required</div>');
        hasError = true;
    }
    if (!dueDate) {
        $('[name="due_date"]').addClass('is-invalid')
            .after('<div class="invalid-feedback d-block">Due date is required</div>');
        hasError = true;
    } else if (invoiceDate && dueDate < invoiceDate) {
        $('[name="due_date"]').addClass('is-invalid')
            .after('<div class="invalid-feedback d-block">Due date must be on or after invoice date</div>');
        hasError = true;
    }

    let lineError = false;
    let selectedCount = 0;
    $('#linesBody tr').each(function() {
        const kind = $(this).attr('data-kind');
        if (!kind) return;
        selectedCount++;

        const qty = parseFloat($(this).find('.qty-input').val());
        const price = parseFloat($(this).find('.price-input').val());
        if (isNaN(qty) || qty <= 0) {
            $(this).find('.qty-input').addClass('is-invalid');
            lineError = true;
        }
        if (isNaN(price) || price < 0 || (kind === 'legacy-service' && price <= 0)) {
            $(this).find('.price-input').addClass('is-invalid');
            lineError = true;
        }
    });

    if (selectedCount === 0 || hasError || lineError || !buildSubmitPayload()) {
        if (selectedCount === 0) {
            toastr.error('Please add at least one item or service line');
        } else {
            toastr.error('Please fill in all required fields correctly');
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    }
});

$('#invoiceForm').on('change input', '.is-invalid', function() {
    $(this).removeClass('is-invalid');
    $(this).nextAll('.invalid-feedback').first().remove();
});

$(function() {
    ensureTrailingEmptyRow($('#linesBody .line-row').last());
});

ajaxFormSubmit('#invoiceForm', '{{ route("admin.sales-invoices.update", $invoice->id) }}', 'PUT', '{{ route("admin.sales-invoices.show", $invoice->id) }}');
</script>
@endsection
