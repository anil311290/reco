@extends('layouts.app')

@php
    $voucherLabels = [
        'income' => 'Sales',
        'expense' => 'Purchase',
        'payment' => 'Payment',
        'receipt' => 'Receipt',
        'journal' => 'Adjustment',
    ];
    $voucherLabel = $voucherLabels[$voucher->voucher_type] ?? ucfirst($voucher->voucher_type);
    $voucherLines = old('lines', $voucher->lines->map(function ($line) {
        return [
            'account_id' => $line->account_id,
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
            'description' => $line->description,
        ];
    })->values()->toArray());
@endphp

@section('title', 'Edit ' . $voucherLabel . ' Voucher')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Edit {{ $voucherLabel }} Voucher</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.vouchers.type', $voucher->voucher_type) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Vouchers
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="voucherForm" method="POST" action="{{ route('admin.vouchers.update', $voucher->id) }}">
            @csrf
            @method('PUT')

            <input type="hidden" name="voucher_type" value="{{ old('voucher_type', $voucher->voucher_type) }}">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="voucher_date" class="form-label">Voucher Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="voucher_date" name="voucher_date"
                           value="{{ old('voucher_date', optional($voucher->voucher_date)->format('Y-m-d')) }}" required>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="party_id" class="form-label">Party</label>
                    <select class="form-select" id="party_id" name="party_id">
                        <option value="">Select Party (Optional)</option>
                        @foreach($parties as $party)
                            <option value="{{ $party['id'] }}" {{ (string) old('party_id', $voucher->party_id) === (string) $party['id'] ? 'selected' : '' }}>
                                {{ $party['text'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label for="narration" class="form-label">Narration</label>
                    <input type="text" class="form-control" id="narration" name="narration"
                           value="{{ old('narration', $voucher->narration) }}" placeholder="Brief description">
                </div>
            </div>

            <hr>

            <h5 class="mb-3">Voucher Lines</h5>

            <div id="voucherLines">
                @foreach($voucherLines as $index => $line)
                <div class="voucher-line row mb-3" data-index="{{ $index }}">
                    <div class="col-md-4">
                        <label class="form-label">Account <span class="text-danger">*</span></label>
                        <select class="form-select line-account" name="lines[{{ $index }}][account_id]" required>
                            <option value="">Select Account</option>
                            @foreach($accounts as $account)
                                <option value="{{ $account['id'] }}" {{ (string) ($line['account_id'] ?? '') === (string) $account['id'] ? 'selected' : '' }}>{{ $account['text'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Debit <span class="text-danger">*</span></label>
                        <input type="number" class="form-control line-debit" name="lines[{{ $index }}][debit]"
                               value="{{ $line['debit'] ?? 0 }}" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Credit <span class="text-danger">*</span></label>
                        <input type="number" class="form-control line-credit" name="lines[{{ $index }}][credit]"
                               value="{{ $line['credit'] ?? 0 }}" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger remove-line" {{ count($voucherLines) <= 1 ? 'style=display:none;' : '' }}>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="mb-3">
                <button type="button" id="addLine" class="btn btn-outline-primary">
                    <i class="bi bi-plus-circle me-2"></i>Add Line
                </button>
            </div>

            <div class="row">
                <div class="col-md-4 offset-md-8">
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>Total Debit</strong></td>
                            <td class="text-end" id="totalDebit">₹0.00</td>
                        </tr>
                        <tr>
                            <td><strong>Total Credit</strong></td>
                            <td class="text-end" id="totalCredit">₹0.00</td>
                        </tr>
                        <tr>
                            <td><strong>Difference</strong></td>
                            <td class="text-end" id="difference">₹0.00</td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="2"
                          placeholder="Enter any additional notes">{{ old('remarks', $voucher->remarks) }}</textarea>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Update Voucher
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let lineIndex = {{ count($voucherLines) }};

    $('#addLine').on('click', function() {
        const newLine = `
            <div class="voucher-line row mb-3" data-index="${lineIndex}">
                <div class="col-md-4">
                    <select class="form-select line-account" name="lines[${lineIndex}][account_id]" required>
                        <option value="">Select Account</option>
                        @foreach($accounts as $account)
                            <option value="{{ $account['id'] }}">{{ $account['text'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control line-debit" name="lines[${lineIndex}][debit]"
                           value="0" step="0.01" min="0" required>
                </div>
                <div class="col-md-3">
                    <input type="number" class="form-control line-credit" name="lines[${lineIndex}][credit]"
                           value="0" step="0.01" min="0" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-danger remove-line">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        `;

        $('#voucherLines').append(newLine);
        lineIndex++;
        updateRemoveButtons();
    });

    $(document).on('click', '.remove-line', function() {
        $(this).closest('.voucher-line').remove();
        updateRemoveButtons();
        calculateTotals();
    });

    function updateRemoveButtons() {
        const lines = $('.voucher-line');
        if (lines.length <= 1) {
            $('.remove-line').hide();
        } else {
            $('.remove-line').show();
        }
    }

    function calculateTotals() {
        let totalDebit = 0;
        let totalCredit = 0;

        $('.line-debit').each(function() {
            totalDebit += parseFloat($(this).val()) || 0;
        });

        $('.line-credit').each(function() {
            totalCredit += parseFloat($(this).val()) || 0;
        });

        const difference = totalDebit - totalCredit;

        $('#totalDebit').text(formatCurrency(totalDebit));
        $('#totalCredit').text(formatCurrency(totalCredit));
        $('#difference').text(formatCurrency(difference));

        if (difference !== 0) {
            $('#difference').addClass('text-danger').removeClass('text-success');
        } else {
            $('#difference').addClass('text-success').removeClass('text-danger');
        }
    }

    $(document).on('input', '.line-debit, .line-credit', function() {
        calculateTotals();
    });

    ajaxFormSubmit('voucherForm', '{{ route("admin.vouchers.update", $voucher->id) }}', 'PUT', function() {
        window.location.href = '{{ route("admin.vouchers.type", $voucher->voucher_type) }}';
    });

    updateRemoveButtons();
    calculateTotals();
});
</script>
@endpush
