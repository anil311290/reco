@extends('layouts.app')

@section('title', 'Edit Purchase Invoice')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Edit Purchase Invoice #{{ $invoice->invoice_number }}</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.purchase-invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

<form id="invoiceForm">
    @method('PUT')
    @php
        $supplierPartyOptions = collect($partyOptions['parties'] ?? [])->where('type', 'creditor')->values();
        $customerPartyOptions = collect($partyOptions['parties'] ?? [])->where('type', 'debtor')->values();
        $supplierLedgerOptions = $partyOptions['cash_bank_od_accounts'] ?? [];
    @endphp
    <div id="builtPayload"></div>
    <div class="row g-4">
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Invoice Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" value="{{ $invoice->invoice_number }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Supplier Invoice #</label>
                            <input type="text" class="form-control" name="supplier_invoice_number" value="{{ $invoice->supplier_invoice_number }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="invoice_date" value="{{ $invoice->invoice_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1">Supplier / Ledger <span class="text-danger">*</span></label>
                            <select class="form-select" name="party_id" id="party_id" data-quick-add-value-mode="token" data-quick-add-in-select="1" data-quick-add-party-type="creditor" data-quick-add-target="#party_id" required>
                                <option value="">Select Supplier or Ledger Account</option>
                                <optgroup label="Quick Actions">
                                <option value="__quick_add_party">+ Quick Add Party</option>
                                <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                                </optgroup>
                                @if($supplierPartyOptions->isNotEmpty())
                                <optgroup label="Suppliers (Parties)">
                                @foreach($supplierPartyOptions as $option)
                                <option value="{{ $option['value'] }}" {{ ('party:' . $invoice->party_id) === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                @endforeach
                                </optgroup>
                                @endif
                                @if($customerPartyOptions->isNotEmpty())
                                <optgroup label="Customers (Parties)">
                                @foreach($customerPartyOptions as $option)
                                    <option value="{{ $option['value'] }}" {{ ('party:' . $invoice->party_id) === $option['value'] ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                @endforeach
                                </optgroup>
                                @endif
                                @if(!empty($supplierLedgerOptions))
                                <optgroup label="Ledger Accounts (Cash/Bank/OD)">
                                    @foreach($supplierLedgerOptions as $option)
                                    <option value="{{ $option['value'] }}">{{ $option['label'] }}</option>
                                    @endforeach
                                </optgroup>
                                @endif
                            </select>
                            <small class="text-muted d-block mt-1">Choose an existing supplier party or select a ledger account for direct posting.</small>
                        </div>
                        <div class="col-md-4">
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

            <!-- Line Items -->
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
                                    <th style="width:28%">Item / Description</th>
                                    <th style="width:10%;">Qty</th>
                                    <th style="width:15%;">Unit Price</th>
                                    <th style="width:10%;">Disc %</th>
                                    <th style="width:12%;">Tax</th>
                                    <th style="width:12%">Total</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody id="linesBody">
                                @forelse($invoice->lines->where('line_type', 'item')->values() as $line)
                                @include('admin.purchase-invoices._line-row', ['line' => $line])
                                @empty
                                @include('admin.purchase-invoices._line-row', ['line' => null])
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="col-md-3">
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
                    @if($invoice->status === 'draft')
                        <button type="submit" class="btn btn-primary w-100 mb-2" id="postInvoiceBtn">
                            <i class="bi bi-check-circle me-2"></i>Save &amp; Post
                        </button>
                        <button type="submit" class="btn btn-outline-secondary w-100" id="saveAsDraftBtn">
                            <i class="bi bi-save me-2"></i>Save as Draft
                        </button>
                        <input type="hidden" name="save_as_draft" id="save_as_draft_flag" value="0">
                    @else
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-check-circle me-2"></i>Update Invoice
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</form>

@include('admin.items._quick-add-item-modal', ['quickAddGoodsOnly' => true])

<template id="lineRowTemplate">
    @include('admin.purchase-invoices._line-row', ['line' => null])
</template>
@endsection

@section('scripts')
<script>
function addLineRow() {
    const row = $($('#lineRowTemplate').html());
    $('#linesBody').append(row);
    initSearchableSelects(row);

    return row;
}

function ensureTrailingEmptyRow(row) {
    const isLastRow = row.is($('#linesBody .line-row').last());

    if (isLastRow && row.find('.item-select').val()) {
        addLineRow();
    }
}

function appendHidden(name, value) {
    $('<input>', { type: 'hidden', name: name, value: value ?? '' }).appendTo('#builtPayload');
}

function buildSubmitPayload() {
    $('#builtPayload').empty();
    let index = 0;

    $('#linesBody .line-row').each(function() {
        const row = $(this);
        const option = row.find('.item-select option:selected');

        if (!row.find('.item-select').val()) {
            return;
        }

        appendHidden(`lines[${index}][item_id]`, option.val());
        appendHidden(`lines[${index}][description]`, row.find('.description-input').val() || '');
        appendHidden(`lines[${index}][quantity]`, row.find('.qty-input').val() || 1);
        appendHidden(`lines[${index}][unit_price]`, row.find('.price-input').val() || 0);
        appendHidden(`lines[${index}][discount_percentage]`, row.find('.disc-input').val() || 0);
        appendHidden(`lines[${index}][tax_rate_id]`, row.find('.tax-select').val() || '');
        index++;
    });

    return index > 0;
}

let quickAddTargetRow = null;
const quickAddItemModalElement = document.getElementById('quickAddItemModal');
const quickAddItemModal = bootstrap.Modal.getOrCreateInstance(quickAddItemModalElement);

function buildQuickItemOption(item) {
    return $('<option>', {
        value: item.id,
        text: item.name
    }).attr({
        'data-price': item.purchase_price || 0,
        'data-tax': item.tax_rate_id || '',
        'data-description': item.description || ''
    });
}

$(document).on('focus select2:open', '.item-select', function() {
    quickAddTargetRow = $(this).closest('.line-row');
});

$('#quickAddItem').on('click', function() {
    if (!quickAddTargetRow || !document.body.contains(quickAddTargetRow[0])) {
        quickAddTargetRow = $('#linesBody .line-row').filter(function() {
            return !$(this).find('.item-select').val();
        }).first();
    }

    if (!quickAddTargetRow.length) {
        quickAddTargetRow = addLineRow();
    }

    $('#quickAddItemForm')[0].reset();
    clearValidationErrors('#quickAddItemForm');
    $('#quick_item_type').val('goods');
    $('#quick_item_unit').prop('disabled', false);
    $('#quick_item_stockable').val('1');
    quickAddItemModal.show();
    quickAddItemModalElement.addEventListener('shown.bs.modal', function() {
        $('#quick_item_name').trigger('focus');
    }, { once: true });
});

ajaxFormSubmit(
    '#quickAddItemForm',
    '{{ route("admin.purchase-invoices.quick-add-item") }}',
    'POST',
    function(response) {
        const item = response.data;

        $('.item-select').each(function() {
            $(this).append(buildQuickItemOption(item));
        });

        const templateContent = document.getElementById('lineRowTemplate').content;
        $(templateContent).find('.item-select').append(buildQuickItemOption(item));

        quickAddTargetRow.find('.item-select').val(String(item.id)).trigger('change');

        quickAddItemModal.hide();
        $('#quickAddItemForm')[0].reset();
        quickAddTargetRow = null;
    }
);

$('#addLine').on('click', function() {
    addLineRow();
});

$('#saveAsDraftBtn').on('click', function() {
    $('#save_as_draft_flag').val('1');
});

$('#postInvoiceBtn').on('click', function() {
    $('#save_as_draft_flag').val('0');
});

$(document).on('click', '.remove-line', function() {
    if ($('#linesBody tr').length > 1) {
        $(this).closest('tr').remove();
        calculateTotals();
    }
});

$(document).on('change', '.item-select', function() {
    const row = $(this).closest('tr');
    const option = $(this).find('option:selected');
    const hasItem = Boolean($(this).val());

    row.find('.description-input').val(hasItem ? (option.attr('data-description') || '') : '');
    row.find('.price-input').val(hasItem ? (option.data('price') || 0) : 0);
    row.find('.tax-select').val(hasItem ? (option.data('tax') || '') : '').trigger('change');

    calculateLineTotal(row);
    ensureTrailingEmptyRow(row);
});

$(document).on('input', '.qty-input, .price-input, .disc-input', function() {
    calculateLineTotal($(this).closest('tr'));
});

$(document).on('change', '.tax-select', function() {
    calculateLineTotal($(this).closest('tr'));
});

function calculateLineTotal(row) {
    let qty = parseFloat(row.find('.qty-input').val()) || 0;
    let price = parseFloat(row.find('.price-input').val()) || 0;
    let disc = parseFloat(row.find('.disc-input').val()) || 0;
    let taxRate = parseFloat(row.find('.tax-select').find(':selected').data('rate')) || 0;

    let base = qty * price;
    let discAmount = base * (disc / 100);
    let afterDisc = base - discAmount;
    let tax = afterDisc * (taxRate / 100);
    let total = afterDisc + tax;

    row.find('.line-total').val('₹' + total.toFixed(2));
    calculateTotals();
}

function calculateTotals() {
    let itemsSubtotal = 0;
    let itemsDiscount = 0;
    let totalTax = 0;

    $('#linesBody tr').each(function() {
        let qty = parseFloat($(this).find('.qty-input').val()) || 0;
        let price = parseFloat($(this).find('.price-input').val()) || 0;
        let disc = parseFloat($(this).find('.disc-input').val()) || 0;
        let taxRate = parseFloat($(this).find('.tax-select').find(':selected').data('rate')) || 0;

        let base = qty * price;
        let discAmount = base * (disc / 100);
        let afterDisc = base - discAmount;
        let tax = afterDisc * (taxRate / 100);

        itemsSubtotal += afterDisc;
        itemsDiscount += discAmount;
        totalTax += tax;
    });

    let subtotal = itemsSubtotal;
    let total = subtotal + totalTax;

    $('#subtotal').text('₹' + subtotal.toFixed(2));
    $('#discountAmount').text('-₹' + itemsDiscount.toFixed(2));
    $('#taxAmount').text('₹' + totalTax.toFixed(2));
    $('#totalAmount').text('₹' + total.toFixed(2));
}

$('#invoiceForm').on('submit.clientValidate', function(e) {
    let hasError = false;
    let lineError = false;

    let form = $(this);
    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();

    const party = form.find('[name="party_id"]');
    const invoiceDate = form.find('[name="invoice_date"]');
    const dueDate = form.find('[name="due_date"]');

    if (!party.val()) {
        party.addClass('is-invalid')
            .after('<div class="invalid-feedback d-block">Please select a supplier</div>');
        hasError = true;
    }

    if (!invoiceDate.val()) {
        invoiceDate.addClass('is-invalid')
            .after('<div class="invalid-feedback d-block">Invoice date is required</div>');
        hasError = true;
    }

    if (!dueDate.val()) {
        dueDate.addClass('is-invalid')
            .after('<div class="invalid-feedback d-block">Due date is required</div>');
        hasError = true;
    } else if (invoiceDate.val() && dueDate.val() < invoiceDate.val()) {
        dueDate.addClass('is-invalid')
            .after('<div class="invalid-feedback d-block">Due date must be on or after invoice date</div>');
        hasError = true;
    }

    let selectedCount = 0;
    $('#linesBody .line-row').each(function() {
        if (!$(this).find('.item-select').val()) {
            return;
        }

        selectedCount++;
        const qty = parseFloat($(this).find('.qty-input').val());
        const price = parseFloat($(this).find('.price-input').val());

        if (isNaN(qty) || qty <= 0) {
            $(this).find('.qty-input').addClass('is-invalid');
            lineError = true;
        }
        if (isNaN(price) || price < 0) {
            $(this).find('.price-input').addClass('is-invalid');
            lineError = true;
        }
    });

    if (selectedCount === 0 || hasError || lineError || !buildSubmitPayload()) {
        toastr.error(selectedCount === 0
            ? 'Please add at least one item line'
            : 'Please fill in all required fields correctly');
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

    function addOneMonth(dateString) {
        if (!dateString) return '';

        const [year, month, day] = dateString.split('-').map(Number);
        const date = new Date(year, month - 1, day);
        date.setMonth(date.getMonth() + 1);

        const nextYear = date.getFullYear();
        const nextMonth = String(date.getMonth() + 1).padStart(2, '0');
        const nextDay = String(date.getDate()).padStart(2, '0');

        return nextYear + '-' + nextMonth + '-' + nextDay;
    }

    function syncDueDateFromInvoice() {
        const invoiceDate = $('[name="invoice_date"]').val();
        if (invoiceDate) {
            $('[name="due_date"]').val(addOneMonth(invoiceDate));
        }
    }

    $('[name="invoice_date"]').on('input change', function() {
        syncDueDateFromInvoice();
    });

    syncDueDateFromInvoice();
});

ajaxFormSubmit('#invoiceForm', '{{ route("admin.purchase-invoices.update", $invoice->id) }}', 'POST', '{{ route("admin.purchase-invoices.index") }}');
</script>
@endsection
