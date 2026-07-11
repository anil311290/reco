@extends('layouts.app')

@php
    $voucherLabels = [
        'income' => 'Sales',
        'expense' => 'Purchase',
        'payment' => 'Payment',
        'receipt' => 'Receipt',
        'journal' => 'Adjustment',
    ];
    $voucherLabel = $voucherLabels[$type] ?? ucfirst($type);
    $isPaymentReceipt = in_array($type, ['payment', 'receipt'], true);
    $isAdjustment = in_array($type, ['journal', 'adjustment'], true);
@endphp

@section('title', 'Create ' . $voucherLabel . ' Voucher')

@section('content')
<div class="row mb-4">
    <div class="col-md-6">
        <h4 class="mb-0">Create {{ $voucherLabel }} Voucher</h4>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="{{ route('admin.vouchers.type', $type) }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Vouchers
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <form id="voucherForm" method="POST" action="{{ route('admin.vouchers.store') }}">
            @csrf
            
            <input type="hidden" name="voucher_type" value="{{ $type }}">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="voucher_date" class="form-label">Voucher Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="voucher_date" name="voucher_date" 
                           value="{{ old('voucher_date', date('Y-m-d')) }}" required>
                </div>

                @if($isPaymentReceipt)
                <div class="col-md-3 mb-3">
                    <label for="payment_mode" class="form-label">Payment Mode <span class="text-danger">*</span></label>
                    <select class="form-select" id="payment_mode" name="payment_mode" required>
                        <option value="">Select Mode</option>
                        <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                        <option value="bank" {{ old('payment_mode') === 'bank' ? 'selected' : '' }}>Bank</option>
                        <option value="od" {{ old('payment_mode') === 'od' ? 'selected' : '' }}>OD</option>
                    </select>
                </div>
                <div class="col-md-3 mb-3">
                    <label for="cash_bank_account_id" class="form-label">
                        {{ $type === 'receipt' ? 'Received In' : 'Paid From' }}
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="cash_bank_account_id" name="cash_bank_account_id" required>
                        <option value="">Select Cash / Bank</option>
                    </select>
                    @if($type === 'payment')
                    <small id="cashBankBalanceHint" class="text-muted d-block mt-1"></small>
                    @endif
                </div>
                @endif

                <div class="col-md-{{ $isPaymentReceipt ? '3' : '4' }} mb-3">
                    <label for="narration" class="form-label">Narration</label>
                    <input type="text" class="form-control" id="narration" name="narration" 
                           value="{{ old('narration') }}" placeholder="Brief description">
                </div>
            </div>

            <hr>

            <h5 class="mb-3">{{ $isPaymentReceipt ? 'Particulars' : 'Voucher Lines' }}</h5>
            @if($isPaymentReceipt)
                <div id="paymentReceiptRows" class="mb-3">
                    <div class="payment-receipt-row row g-2 mb-2" data-index="0">
                        <div class="col-md-8">
                            <label class="form-label">Particulars (Account / Party) <span class="text-danger">*</span></label>
                            <select class="form-select pr-particular" name="payment_rows[0][account_id]" required>
                                <option value="">Select Particulars</option>
                                @foreach(($particularsOptions ?? []) as $option)
                                    <option value="{{ $option['id'] }}">{{ $option['text'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control pr-amount" name="payment_rows[0][amount]" value="" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger remove-payment-receipt-row" style="display:none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="button" id="addPaymentReceiptRow" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add Particulars
                    </button>
                    <small class="text-muted ms-2 d-block mt-2">
                        @if($type === 'payment')
                            Payment (Tally style): Dr Particulars (Party/Expense), Cr Cash/Bank — auto-posted to ledger &amp; journal.
                        @else
                            Receipt (Tally style): Dr Cash/Bank, Cr Particulars (Party/Income) — auto-posted to ledger &amp; journal.
                        @endif
                    </small>
                </div>
            @elseif($isAdjustment)
                <div id="adjustmentRows" class="mb-3">
                    <div class="adjustment-row row g-2 mb-2" data-index="0">
                        <div class="col-md-3">
                            <label class="form-label">Creditor Account <span class="text-danger">*</span></label>
                            <select class="form-select adjustment-creditor" name="adjustment_rows[0][creditor_account_id]" required>
                                <option value="">Select Creditor Account</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account['id'] }}">{{ $account['text'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Credit Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control adjustment-credit-amount" name="adjustment_rows[0][credit_amount]" value="" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Debitor Account <span class="text-danger">*</span></label>
                            <select class="form-select adjustment-debitor" name="adjustment_rows[0][debitor_account_id]" required>
                                <option value="">Select Debitor Account</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account['id'] }}">{{ $account['text'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Debit Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control adjustment-debit-amount" name="adjustment_rows[0][debit_amount]" value="" step="0.01" min="0" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger remove-adjustment-row" style="display:none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <button type="button" id="addAdjustmentRow" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add Row
                    </button>
                    <small class="text-muted ms-2">Select Creditor and Debitor accounts. Credit and Debit amounts must be equal.</small>
                </div>
            @else
                <div id="voucherLines">
                    <div class="voucher-line row mb-3" data-index="0">
                        <div class="col-md-4">
                            <label class="form-label">Account <span class="text-danger">*</span></label>
                            <select class="form-select line-account" name="lines[0][account_id]" required>
                                <option value="">Select Account</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account['id'] }}">{{ $account['text'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Debit <span class="text-danger">*</span></label>
                            <input type="number" class="form-control line-debit" name="lines[0][debit]" 
                                   value="0" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Credit <span class="text-danger">*</span></label>
                            <input type="number" class="form-control line-credit" name="lines[0][credit]" 
                                   value="0" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger remove-line" style="display: none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="button" id="addLine" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add Line
                    </button>
                </div>
            @endif

            <div class="row">
                <div class="col-md-4 offset-md-8">
                    @if($isPaymentReceipt)
                    <table class="table table-bordered">
                        <tr>
                            <td><strong>Total Amount</strong></td>
                            <td class="text-end text-success" id="totalAmount">₹0.00</td>
                        </tr>
                    </table>
                    @else
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
                    @endif
                </div>
            </div>

            <div class="mb-3">
                <label for="remarks" class="form-label">Remarks</label>
                <textarea class="form-control" id="remarks" name="remarks" rows="2" 
                          placeholder="Enter any additional notes">{{ old('remarks') }}</textarea>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-circle me-2"></i>Create Voucher
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    const isPaymentReceipt = @json($isPaymentReceipt);
    const isAdjustment = @json($isAdjustment);
    const voucherType = @json($type);
    const cashBankAccounts = @json($cashBankAccounts ?? []);
    const particularsOptions = @json($particularsOptions ?? []);
    const accounts = @json($accounts);
    let lineIndex = 1;
    let paymentReceiptRowIndex = 1;
    let adjustmentRowIndex = 1;

    function cashBankOptionsHtml(selectedMode = '', selectedValue = '') {
        let html = '<option value="">Select Cash / Bank</option>';
        cashBankAccounts.forEach((option) => {
            if (selectedMode && option.transaction_mode !== selectedMode) {
                return;
            }
            const selected = String(selectedValue) === String(option.id) ? 'selected' : '';
            html += `<option value="${option.id}" ${selected}>${option.text}</option>`;
        });
        return html;
    }

    function particularsOptionsHtml(selectedValue = '') {
        let html = '<option value="">Select Particulars</option>';
        particularsOptions.forEach((option) => {
            const selected = String(selectedValue) === String(option.id) ? 'selected' : '';
            html += `<option value="${option.id}" ${selected}>${option.text}</option>`;
        });
        return html;
    }

    function accountOptionsHtml(selectedValue = '') {
        let html = '<option value="">Select Account</option>';
        accounts.forEach((account) => {
            const selected = String(selectedValue) === String(account.id) ? 'selected' : '';
            html += `<option value="${account.id}" ${selected}>${account.text}</option>`;
        });
        return html;
    }

    function calculateTotals() {
        if (isPaymentReceipt) {
            let total = 0;
            $('.payment-receipt-row .pr-amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });
            $('#totalAmount').text(formatCurrency(total));
            return;
        }

        let totalDebit = 0;
        let totalCredit = 0;

        if (isAdjustment) {
            $('.adjustment-row').each(function() {
                totalDebit += parseFloat($(this).find('.adjustment-debit-amount').val()) || 0;
                totalCredit += parseFloat($(this).find('.adjustment-credit-amount').val()) || 0;
            });
        } else {
            $('.line-debit').each(function() {
                totalDebit += parseFloat($(this).val()) || 0;
            });
            $('.line-credit').each(function() {
                totalCredit += parseFloat($(this).val()) || 0;
            });
        }

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

    if (isPaymentReceipt) {
        function updatePaymentReceiptRemoveButtons() {
            if ($('.payment-receipt-row').length <= 1) {
                $('.remove-payment-receipt-row').hide();
            } else {
                $('.remove-payment-receipt-row').show();
            }
        }

        function refreshCashBankDropdown() {
            const mode = $('#payment_mode').val();
            const selected = $('#cash_bank_account_id').val() || '';
            $('#cash_bank_account_id').html(cashBankOptionsHtml(mode, selected));
            updateCashBankBalanceHint();
        }

        function updateCashBankBalanceHint() {
            if (voucherType !== 'payment') {
                return;
            }

            const $hint = $('#cashBankBalanceHint');
            if (!$hint.length) {
                return;
            }

            const accountId = String($('#cash_bank_account_id').val() || '');
            if (!accountId) {
                $hint.text('');
                return;
            }

            const account = cashBankAccounts.find((option) => String(option.id) === accountId);
            if (!account) {
                $hint.text('');
                return;
            }

            if (account.available_balance === null) {
                $hint.text('Overdraft account — no balance limit.').removeClass('text-danger').addClass('text-muted');
                return;
            }

            const available = parseFloat(account.available_balance) || 0;
            let total = 0;
            $('.payment-receipt-row .pr-amount').each(function() {
                total += parseFloat($(this).val()) || 0;
            });

            let text = 'Available balance: ₹' + available.toFixed(2);
            if (total > available + 0.009) {
                text += ' — payment exceeds available balance';
                $hint.removeClass('text-muted').addClass('text-danger');
            } else {
                $hint.removeClass('text-danger').addClass('text-muted');
            }
            $hint.text(text);
        }

        function buildPaymentReceiptRow(index) {
            return $(`
                <div class="payment-receipt-row row g-2 mb-2" data-index="${index}">
                    <div class="col-md-8">
                        <select class="form-select pr-particular" name="payment_rows[${index}][account_id]" required>
                            ${particularsOptionsHtml()}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control pr-amount" name="payment_rows[${index}][amount]" value="" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger remove-payment-receipt-row">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `);
        }

        function getSelectedParticularIds(exceptSelect = null) {
            const ids = [];
            $('.pr-particular').each(function() {
                if (exceptSelect && this === exceptSelect) {
                    return;
                }
                const value = $(this).val();
                if (value) {
                    ids.push(String(value));
                }
            });
            return ids;
        }

        function refreshParticularsAvailability() {
            const usedIds = getSelectedParticularIds();
            $('.pr-particular').each(function() {
                const $select = $(this);
                const currentValue = String($select.val() || '');
                $select.find('option').each(function() {
                    const optionValue = String($(this).attr('value') || '');
                    if (!optionValue) {
                        $(this).prop('disabled', false);
                        return;
                    }
                    const usedElsewhere = usedIds.includes(optionValue) && optionValue !== currentValue;
                    $(this).prop('disabled', usedElsewhere);
                });
            });
        }

        $('#addPaymentReceiptRow').on('click', function() {
            const row = buildPaymentReceiptRow(paymentReceiptRowIndex);
            $('#paymentReceiptRows').append(row);
            paymentReceiptRowIndex++;
            updatePaymentReceiptRemoveButtons();
            refreshParticularsAvailability();
        });

        $(document).on('click', '.remove-payment-receipt-row', function() {
            if ($('.payment-receipt-row').length <= 1) {
                toastr.error('At least one particulars row is required.');
                return;
            }
            $(this).closest('.payment-receipt-row').remove();
            updatePaymentReceiptRemoveButtons();
            refreshParticularsAvailability();
            calculateTotals();
        });

        $(document).on('input', '.payment-receipt-row .pr-amount', function() {
            calculateTotals();
            updateCashBankBalanceHint();
        });

        $(document).on('change', '.pr-particular', function() {
            const $select = $(this);
            const value = String($select.val() || '');
            const cashBankId = String($('#cash_bank_account_id').val() || '');

            if (value && cashBankId && value === cashBankId) {
                toastr.error('Particulars cannot be same as Cash / Bank account.');
                $select.val('');
                refreshParticularsAvailability();
                return;
            }

            if (value && getSelectedParticularIds(this).includes(value)) {
                toastr.error('This particulars account is already selected. Use one row and combine the amount.');
                $select.val('');
            }

            refreshParticularsAvailability();
        });

        $('#payment_mode').on('change', refreshCashBankDropdown);
        $('#cash_bank_account_id').on('change', updateCashBankBalanceHint);
        refreshCashBankDropdown();
        updatePaymentReceiptRemoveButtons();
        refreshParticularsAvailability();
    } else if (isAdjustment) {
        function updateAdjustmentRemoveButtons() {
            if ($('.adjustment-row').length <= 1) {
                $('.remove-adjustment-row').hide();
            } else {
                $('.remove-adjustment-row').show();
            }
        }

        function freezeAdjustmentAccounts(row) {
            const creditorSelect = row.find('.adjustment-creditor');
            const debitorSelect = row.find('.adjustment-debitor');
            const creditorValue = creditorSelect.val();
            const debitorValue = debitorSelect.val();

            debitorSelect.find('option').prop('disabled', false);
            creditorSelect.find('option').prop('disabled', false);

            if (creditorValue) {
                debitorSelect.find(`option[value="${creditorValue}"]`).prop('disabled', true);
            }
            if (debitorValue) {
                creditorSelect.find(`option[value="${debitorValue}"]`).prop('disabled', true);
            }

            if (creditorValue && creditorValue === debitorValue) {
                toastr.error('Creditor and Debitor accounts cannot be the same.');
                debitorSelect.val('');
            }
        }

        $('#addAdjustmentRow').on('click', function() {
            const row = $(`
                <div class="adjustment-row row g-2 mb-2" data-index="${adjustmentRowIndex}">
                    <div class="col-md-3">
                        <select class="form-select adjustment-creditor" name="adjustment_rows[${adjustmentRowIndex}][creditor_account_id]" required>
                            ${accountOptionsHtml()}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control adjustment-credit-amount" name="adjustment_rows[${adjustmentRowIndex}][credit_amount]" value="" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select adjustment-debitor" name="adjustment_rows[${adjustmentRowIndex}][debitor_account_id]" required>
                            ${accountOptionsHtml()}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control adjustment-debit-amount" name="adjustment_rows[${adjustmentRowIndex}][debit_amount]" value="" step="0.01" min="0" placeholder="0.00" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger remove-adjustment-row">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `);

            $('#adjustmentRows').append(row);
            adjustmentRowIndex++;
            updateAdjustmentRemoveButtons();
        });

        $(document).on('change', '.adjustment-creditor, .adjustment-debitor', function() {
            freezeAdjustmentAccounts($(this).closest('.adjustment-row'));
        });

        $(document).on('click', '.remove-adjustment-row', function() {
            $(this).closest('.adjustment-row').remove();
            updateAdjustmentRemoveButtons();
            calculateTotals();
        });

        $(document).on('input', '.adjustment-row .adjustment-debit-amount', function() {
            const row = $(this).closest('.adjustment-row');
            const debit = parseFloat($(this).val()) || 0;
            if (debit > 0) {
                row.find('.adjustment-credit-amount').val(debit);
            }
            calculateTotals();
        });

        $(document).on('input', '.adjustment-row .adjustment-credit-amount', function() {
            const row = $(this).closest('.adjustment-row');
            const credit = parseFloat($(this).val()) || 0;
            if (credit > 0) {
                row.find('.adjustment-debit-amount').val(credit);
            }
            calculateTotals();
        });

        $('.adjustment-row').each(function() {
            freezeAdjustmentAccounts($(this));
        });
        updateAdjustmentRemoveButtons();
    } else {
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

        $(document).on('input', '.line-debit, .line-credit', function() {
            calculateTotals();
        });

        updateRemoveButtons();
    }

    ajaxFormSubmit('voucherForm', '{{ route("admin.vouchers.store") }}', 'POST', function(response) {
        window.location.href = '{{ route("admin.vouchers.index") }}';
    });

    calculateTotals();
});
</script>
@endpush
