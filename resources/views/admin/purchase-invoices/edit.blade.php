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
    <div class="row g-4">
        <div class="col-md-8">
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
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Supplier <span class="text-danger">*</span></label>
                                @permission('parties.create')
                                <button type="button" class="btn btn-link btn-sm p-0 quick-add-party-btn" data-party-quick-add-target="#party_id" data-party-quick-add-type="creditor">Quick Add</button>
                                @endpermission
                            </div>
                            <select class="form-select" name="party_id" id="party_id" required>
                                <option value="">Select Supplier</option>
                                @foreach($parties as $party)
                                <option value="{{ $party->id }}" {{ $invoice->party_id == $party->id ? 'selected' : '' }}>{{ $party->name }} ({{ $party->party_code }})</option>
                                @endforeach
                            </select>
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
                                @forelse($invoice->lines->where('line_type', 'item')->values() as $lineIdx => $line)
                                <tr class="line-row">
                                    <td>
                                        <select class="form-select form-select-sm item-select w-100" name="lines[{{ $lineIdx }}][item_id]">
                                            <option value="">Select Item</option>
                                            @foreach($items as $item)
                                            <option value="{{ $item->id }}" data-price="{{ $item->purchase_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}" {{ $line->item_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1 bg-light" name="lines[{{ $lineIdx }}][description]" value="{{ $line->description }}" placeholder="Description" readonly>
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm qty-input" name="lines[{{ $lineIdx }}][quantity]" value="{{ $line->quantity }}" min="0.001" step="0.001"></td>
                                    <td><input type="number" class="form-control form-control-sm price-input" name="lines[{{ $lineIdx }}][unit_price]" value="{{ $line->unit_price }}" min="0" step="0.01"></td>
                                    <td><input type="number" class="form-control form-control-sm disc-input" name="lines[{{ $lineIdx }}][discount_percentage]" value="{{ $line->discount_percentage ?? 0 }}" min="0" max="100" step="0.01"></td>
                                    <td>
                                        <select class="form-select form-select-sm tax-select w-100" name="lines[{{ $lineIdx }}][tax_rate_id]">
                                            <option value="">No Tax</option>
                                            @foreach($taxRates as $tax)
                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}" {{ $line->tax_rate_id == $tax->id ? 'selected' : '' }}>{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm line-total" value="₹{{ number_format($line->total, 2) }}" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-line"><i class="bi bi-trash"></i></button></td>
                                </tr>
                                @empty
                                <tr class="line-row">
                                    <td>
                                        <select class="form-select form-select-sm item-select w-100" name="lines[0][item_id]">
                                            <option value="">Select Item</option>
                                            @foreach($items as $item)
                                            <option value="{{ $item->id }}" data-price="{{ $item->purchase_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1 bg-light" name="lines[0][description]" placeholder="Description" readonly>
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
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Summary -->
        <div class="col-md-4">
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
@endsection

@section('scripts')
<script>
let lineIndex = {{ $invoice->lines->where('line_type', 'item')->count() + 1 }};

$('#addLine').on('click', function() {
    let row = `<tr class="line-row">
        <td>
            <select class="form-select form-select-sm item-select w-100" name="lines[${lineIndex}][item_id]">
                <option value="">Select Item</option>
                @foreach($items as $item)
                <option value="{{ $item->id }}" data-price="{{ $item->purchase_price }}" data-tax="{{ $item->tax_rate_id }}" data-description="{{ e($item->description ?? '') }}">{{ $item->name }}</option>
                @endforeach
            </select>
            <input type="text" class="form-control form-control-sm mt-1 bg-light" name="lines[${lineIndex}][description]" placeholder="Description" readonly>
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
    let price = option.data('price') || 0;
    let taxId = option.data('tax') || '';
    let description = option.attr('data-description') || '';
    let descInput = row.find('input[name*="[description]"]');

    descInput.val($(this).val() ? description : '');

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

    if (hasError || lineError) {
        toastr.error('Please fill in all required fields correctly');
        e.preventDefault();
        e.stopImmediatePropagation();
        return false;
    }
});

$('#invoiceForm').on('change input', '.is-invalid', function() {
    $(this).removeClass('is-invalid');
    $(this).nextAll('.invalid-feedback').first().remove();
});

ajaxFormSubmit('#invoiceForm', '{{ route("admin.purchase-invoices.update", $invoice->id) }}', 'POST', '{{ route("admin.purchase-invoices.index") }}');
</script>
@endsection
