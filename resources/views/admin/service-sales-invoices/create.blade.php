@extends('layouts.app')

@section('title', 'Create Service Sales Invoice')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Create Service Sales Invoice</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.service-sales-invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to List
        </a>
    </div>
</div>

<form id="invoiceForm" method="POST">
    @csrf
    <div class="row g-4">
        <!-- Invoice Details -->
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
                            <label class="form-label">Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="invoice_date" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Due Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="due_date" value="{{ date('Y-m-d', strtotime('+30 days')) }}" required>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0">Customer <span class="text-danger">*</span></label>
                                @permission('parties.create')
                                <button type="button" class="btn btn-link btn-sm p-0 quick-add-party-btn" data-party-quick-add-target="#party_id" data-party-quick-add-type="debtor">Quick Add</button>
                                @endpermission
                            </div>
                            <select class="form-select" name="party_id" id="party_id" required>
                                <option value="">Select Customer</option>
                                @foreach($parties as $party)
                                <option value="{{ $party->id }}">{{ $party->name }} ({{ $party->party_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reference #</label>
                            <input type="text" class="form-control" name="reference_number" placeholder="PO/REF">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment/Delivery Terms</label>
                            <input type="text" class="form-control" name="payment_terms" placeholder="e.g., Net 30, FOB">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Additional notes..."></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Line Items - ONLY SERVICE ITEMS (No regular Line Items) -->
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
                                    <th style="width:20%;">Tax</th>
                                    <th style="width:15%">Total</th>
                                    <th style="width:5%"></th>
                                </tr>
                            </thead>
                            <tbody id="serviceLinesBody">
                                <tr class="service-line-row">
                                    <td>
                                        <select class="form-select form-select-sm service-account-select" name="service_lines[0][account_id]">
                                            <option value="">Select Service Account</option>
                                            @foreach($serviceAccounts as $account)
                                            <option value="{{ $account['id'] }}">{{ $account['text'] }}</option>
                                            @endforeach
                                        </select>
                                        <input type="text" class="form-control form-control-sm mt-1" name="service_lines[0][description]" placeholder="Description">
                                    </td>
                                    <td><input type="number" class="form-control form-control-sm service-amount-input" name="service_lines[0][amount]" value="0" min="0" step="0.01"></td>
                                    <td>
                                        <select class="form-select form-select-sm service-tax-select" name="service_lines[0][tax_rate_id]">
                                            <option value="">No Tax</option>
                                            @foreach($taxRates as $tax)
                                            <option value="{{ $tax->id }}" data-rate="{{ $tax->rate }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" class="form-control form-control-sm service-line-total" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-outline-danger remove-service-line"><i class="bi bi-trash"></i></button></td>
                                </tr>
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
                        <strong id="subtotal">₹0.00</strong>
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

$(document).on('submit', '#invoiceForm', function(e) {
    let form = $(this);
    let hasError = false;

    form.find('.is-invalid').removeClass('is-invalid');
    form.find('.invalid-feedback').remove();

    const party = form.find('[name="party_id"]');
    const invoiceDate = form.find('[name="invoice_date"]');
    const dueDate = form.find('[name="due_date"]');
    const serviceRows = $('#serviceLinesBody tr');

    if (!party.val()) {
        party.addClass('is-invalid')
            .after('<div class="invalid-feedback d-block">Please select a customer</div>');
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

    if (serviceRows.length === 0) {
        hasError = true;
    }

    serviceRows.each(function() {
        const amountInput = $(this).find('.service-amount-input');
        const amount = parseFloat(amountInput.val());

        if (isNaN(amount) || amount < 0) {
            amountInput.addClass('is-invalid')
                .after('<div class="invalid-feedback d-block">Amount must be a valid number</div>');
            hasError = true;
        }
    });

    if (hasError) {
        e.preventDefault();
        e.stopImmediatePropagation();
        toastr.error('Please fill in all required fields correctly');
        return false;
    }
});

$(document).on('change input', '#invoiceForm .is-invalid', function() {
    $(this).removeClass('is-invalid');
    $(this).nextAll('.invalid-feedback').first().remove();
});

$(document).on('click', '.remove-service-line', function() {
    $(this).closest('tr').remove();
    calculateTotals();
});

$(document).on('input', '.service-amount-input', function() {
    calculateServiceLineTotal($(this).closest('tr'));
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

function calculateTotals() {
    let subtotal = 0;
    let totalTax = 0;

    $('#serviceLinesBody tr').each(function() {
        let amount = parseFloat($(this).find('.service-amount-input').val()) || 0;
        let taxRate = parseFloat($(this).find('.service-tax-select').find(':selected').data('rate')) || 0;

        let tax = amount * (taxRate / 100);

        subtotal += amount;
        totalTax += tax;
    });

    let total = subtotal + totalTax;

    $('#subtotal').text('₹' + subtotal.toFixed(2));
    $('#taxAmount').text('₹' + totalTax.toFixed(2));
    $('#totalAmount').text('₹' + total.toFixed(2));
}

ajaxFormSubmit('#invoiceForm', '{{ route("admin.service-sales-invoices.store") }}', 'POST', '{{ route("admin.service-sales-invoices.index") }}');
</script>
@endsection
