@extends('layouts.app')

@section('title', 'Create Purchase Invoice')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Create Purchase Invoice</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.purchase-invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

<form id="invoiceForm">
    <div class="row g-4">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header"><h5 class="mb-0">Invoice Details</h5></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" value="{{ $invoiceNumber }}" readonly>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Supplier Invoice #</label>
                            <input type="text" class="form-control" name="supplier_invoice_number" placeholder="Supplier's ref">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="invoice_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="due_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Supplier <span class="text-danger">*</span></label>
                                @permission('parties.create')
                                <button type="button" class="btn btn-link btn-sm p-0 quick-add-party-btn" data-party-quick-add-target="#party_id" data-party-quick-add-type="creditor">Quick Add</button>
                                @endpermission
                            </div>
                            <select class="form-select" name="party_id" id="party_id" required>
                                <option value="">Select Supplier</option>
                                @foreach($parties as $party)
                                <option value="{{ $party->id }}">{{ $party->name }} ({{ $party->party_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Payment/Delivery Terms</label>
                            <input type="text" class="form-control" name="payment_terms" placeholder="e.g., Net 30, FOB">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Line Items</h5>
                    <button type="button" class="btn btn-sm btn-primary" id="addLine">
                        <i class="bi bi-plus-circle me-1"></i>Add Line
                    </button>
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
                                <tr class="line-row">
                                    <td>
                                        <select class="form-select form-select-sm item-select w-100" name="lines[0][item_id]">
                                            <option value="">Select Item</option>
                                            @foreach($items as $item)
                                            <option value="{{ $item->id }}" data-price="{{ $item->purchase_price }}" data-tax="{{ $item->tax_rate_id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1" name="lines[0][description]" placeholder="Description">
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm qty-input" name="lines[0][quantity]" value="1" min="0.001" step="0.001"></td>
                                    <td><input type="number" class="form-control form-control-sm price-input" name="lines[0][unit_price]" value="0" min="0" step="0.01"></td>
                                    <td><input type="number" class="form-control form-control-sm disc-input" name="lines[0][discount_percentage]" value="0" min="0" max="100" step="0.01"></td>
                                    <td>
                                        <select class="form-select form-select-sm tax-select w-100" name="lines[0][tax_rate_id]">
                                            <option value="">No Tax</option>
                                            @foreach($taxRates as $tax)
                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm line-total" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Service Line Items -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Service Line Items</h5>
                    <button type="button" class="btn btn-sm btn-primary" id="addServiceLine">
                        <i class="bi bi-plus-circle me-1"></i>Add Service
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0" id="serviceTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40%">Service Account / Description</th>
                                    <th style="width:15%">Amount</th>
                                    <th style="width:15%;">Tax</th>
                                    <th style="width:15%">Total</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody id="serviceLinesBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card sticky-top" style="top:1rem">
                <div class="card-header"><h5 class="mb-0">Summary</h5></div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <strong id="subtotal">₹0.00</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Discount:</span>
                        <span class="text-danger" id="discountAmount">-₹0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax:</span>
                        <span id="taxAmount">₹0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fs-5 fw-bold">Total:</span>
                        <span class="fs-5 fw-bold text-primary" id="totalAmount">₹0.00</span>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle me-2"></i>Create Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@section('scripts')
<script>
let lineIndex = 1;

$('#addLine').on('click', function() {
    let row = `<tr class="line-row">
        <td>
            <select class="form-select form-select-sm item-select w-100" name="lines[${lineIndex}][item_id]">
                <option value="">Select Item</option>
                @foreach($items as $item)
                <option value="{{ $item->id }}" data-price="{{ $item->purchase_price }}" data-tax="{{ $item->tax_rate_id }}">{{ $item->name }}</option>
                @endforeach
            </select>
            <input type="text" class="form-control form-control-sm mt-1" name="lines[${lineIndex}][description]" placeholder="Description">
        </td>
        <td><input type="number" class="form-control form-control-sm qty-input" name="lines[${lineIndex}][quantity]" value="1" min="0.001" step="0.001"></td>
        <td><input type="number" class="form-control form-control-sm price-input" name="lines[${lineIndex}][unit_price]" value="0" min="0" step="0.01"></td>
        <td><input type="number" class="form-control form-control-sm disc-input" name="lines[${lineIndex}][discount_percentage]" value="0" min="0" max="100" step="0.01"></td>
        <td>
            <select class="form-select form-select-sm tax-select w-100" name="lines[${lineIndex}][tax_rate_id]">
                <option value="">No Tax</option>
                @foreach($taxRates as $tax)
                <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm line-total" readonly></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    $('#linesBody').append(row);
    lineIndex++;
});

$(document).on('click', '.remove-line', function() {
    if ($('#linesBody tr').length > 1) {
        $(this).closest('tr').remove();
        calculateTotals();
    }
});

$(document).on('change', '.item-select', function() {
    let row = $(this).closest('tr');
    let option = $(this).find(':selected');
    let itemName = option.text().trim();
    let price = option.data('price') || 0;
    let taxId = option.data('tax') || '';
    let descInput = row.find('input[name*="[description]"]');

    if (itemName && itemName !== 'Select Item' && !descInput.val()) {
        descInput.val(itemName);
    }

    row.find('.price-input').val(price);
    row.find('.tax-select').val(taxId);
    calculateLineTotal(row);
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

    // Line Items
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

    // Service Lines
    let serviceSubtotal = 0;
    $('#serviceLinesBody tr').each(function() {
        let amount = parseFloat($(this).find('.service-amount-input').val()) || 0;
        let taxRate = parseFloat($(this).find('.service-tax-select').find(':selected').data('rate')) || 0;

        let tax = amount * (taxRate / 100);
        serviceSubtotal += amount;
        totalTax += tax;
    });

    let subtotal = itemsSubtotal + serviceSubtotal;
    let total = subtotal + totalTax;

    $('#subtotal').text('₹' + subtotal.toFixed(2));
    $('#discountAmount').text('-₹' + itemsDiscount.toFixed(2));
    $('#taxAmount').text('₹' + totalTax.toFixed(2));
    $('#totalAmount').text('₹' + total.toFixed(2));
}

// Service Lines Handling
let serviceLineIndex = 0;

$('#addServiceLine').on('click', function() {
    let row = `<tr class="service-line-row">
        <td>
            <select class="form-select form-select-sm service-account-select" name="service_lines[${serviceLineIndex}][account_id]">
                <option value="">Select Service Account</option>
                @foreach($serviceAccounts as $account)
                <option value="{{ $account['id'] }}">{{ $account['text'] }}</option>
                @endforeach
            </select>
            <input type="text" class="form-control form-control-sm mt-1" name="service_lines[${serviceLineIndex}][description]" placeholder="Description">
        </td>
        <td><input type="number" class="form-control form-control-sm service-amount-input" name="service_lines[${serviceLineIndex}][amount]" value="0" min="0" step="0.01"></td>
        <td>
            <select class="form-select form-select-sm service-tax-select" name="service_lines[${serviceLineIndex}][tax_rate_id]">
                <option value="">No Tax</option>
                @foreach($taxRates as $tax)
                <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                @endforeach
            </select>
        </td>
        <td><input type="text" class="form-control form-control-sm service-line-total" readonly></td>
        <td><button type="button" class="btn btn-sm btn-outline-danger remove-service-line"><i class="bi bi-trash"></i></button></td>
    </tr>`;
    $('#serviceLinesBody').append(row);
    serviceLineIndex++;
});

$(document).on('click', '.remove-service-line', function() {
    $(this).closest('tr').remove();
    calculateTotals();
});

$(document).on('input', '.service-amount-input', function() {
    calculateServiceLineTotal($(this).closest('tr'));
});

// Client-side pre-validation (runs BEFORE ajaxFormSubmit handler)
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

    $('#linesBody tr').each(function() {
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

    $('#serviceLinesBody tr').each(function() {
        const amountInput = $(this).find('.service-amount-input');
        const amount = parseFloat(amountInput.val());

        if (isNaN(amount) || amount < 0) {
            amountInput.addClass('is-invalid')
                .after('<div class="invalid-feedback d-block">Amount must be a valid number</div>');
            lineError = true;
        }
    });

    if (hasError || lineError) {
        toastr.error('Please fill in all required fields correctly');
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    }
});

// Clear validation state on field change
$('#invoiceForm').on('change input', '.is-invalid', function() {
    $(this).removeClass('is-invalid');
    $(this).nextAll('.invalid-feedback').first().remove();
});

$(document).on('change', '.service-account-select', function() {
    let row = $(this).closest('tr');
    let accountName = $(this).find(':selected').text().trim();
    let descInput = row.find('input[name*="[description]"]');
    if (accountName && accountName !== 'Select Service Account' && !descInput.val()) {
        descInput.val(accountName);
    }
});

$(document).on('change', '.service-tax-select', function() {
    calculateServiceLineTotal($(this).closest('tr'));
});

function calculateServiceLineTotal(row) {
    let amount = parseFloat(row.find('.service-amount-input').val()) || 0;
    let taxRate = parseFloat(row.find('.service-tax-select').find(':selected').data('rate')) || 0;

    let tax = amount * (taxRate / 100);
    let total = amount + tax;

    row.find('.service-line-total').val('₹' + total.toFixed(2));
    calculateTotals();
}

ajaxFormSubmit('#invoiceForm', '{{ route("admin.purchase-invoices.store") }}', 'POST', '{{ route("admin.purchase-invoices.index") }}');
</script>
@endsection
