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
    $isPaymentReceipt = in_array($voucher->voucher_type, ['payment', 'receipt'], true);
    $isAdjustment = in_array($voucher->voucher_type, ['journal', 'adjustment'], true);
    $voucherLines = old('lines', $voucher->lines->map(function ($line) {
        return [
            'account_id' => $line->account_id,
            'debit' => (float) $line->debit,
            'credit' => (float) $line->credit,
            'description' => $line->description,
        ];
    })->values()->toArray());

    $adjustmentRows = old('adjustment_rows', []);
    $paymentRows = old('payment_rows', []);
    $selectedCashBankAccountId = old('cash_bank_account_id');
    $cashBankAccountIds = collect($cashBankAccounts ?? [])->pluck('id')->map(fn ($id) => (int) $id)->all();

    if ($isPaymentReceipt && empty($paymentRows)) {
        foreach ($voucher->lines as $line) {
            $isCashBank = in_array((int) $line->account_id, $cashBankAccountIds, true);

            if ($isCashBank) {
                if (!$selectedCashBankAccountId) {
                    $selectedCashBankAccountId = $line->account_id;
                }
                continue;
            }

            $amount = (float) $line->debit > 0 ? (float) $line->debit : (float) $line->credit;
            $paymentRows[] = [
                'account_id' => $line->account_id,
                'amount' => $amount > 0 ? $amount : '',
            ];
        }
    }

    if ($isAdjustment && empty($adjustmentRows)) {
        foreach ($voucher->lines as $line) {
            if ((float) $line->debit > 0) {
                $adjustmentRows[] = [
                    'account_id' => $line->account_id,
                    'entry_type' => 'debit',
                    'amount' => $line->debit,
                ];
            } elseif ((float) $line->credit > 0) {
                $adjustmentRows[] = [
                    'account_id' => $line->account_id,
                    'entry_type' => 'credit',
                    'amount' => $line->credit,
                ];
            }
        }
    }

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

                @if($isPaymentReceipt)
                <div class="col-md-4 mb-3">
                    <label for="cash_bank_account_id" class="form-label">
                        {{ $voucher->voucher_type === 'receipt' ? 'Received In' : 'Paid From' }}
                        <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="cash_bank_account_id" name="cash_bank_account_id" data-selected="{{ $selectedCashBankAccountId }}" data-quick-add-in-select="1" data-quick-add-target="#cash_bank_account_id" required>
                        <option value="">Select Cash / Bank / OD</option>
                        <optgroup label="Quick Actions">
                            <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                        </optgroup>
                    </select>
                </div>
                @endif

                <div class="col-md-4 mb-3">
                    <label for="narration" class="form-label">Narration</label>
                    <textarea class="form-control" id="narration" name="narration" rows="2"
                              placeholder="Brief description">{{ old('narration', $voucher->narration) }}</textarea>
                </div>
            </div>

            <hr>

            <h5 class="mb-3">{{ $isPaymentReceipt ? 'Particulars' : 'Voucher Lines' }}</h5>
            @if($isPaymentReceipt)
                <div id="paymentReceiptRows" class="mb-3">
                    @forelse($paymentRows as $index => $row)
                    <div class="payment-receipt-row row g-2 mb-2" data-index="{{ $index }}">
                        <div class="col-md-8">
                            <label class="form-label">Particulars <span class="text-danger">*</span></label>
                            <select class="form-select pr-particular" id="payment_particular_{{ $index }}" name="payment_rows[{{ $index }}][account_id]" data-quick-add-value-mode="token" data-quick-add-in-select="1" data-quick-add-party-type="{{ $voucher->voucher_type === 'payment' ? 'creditor' : 'debtor' }}" data-quick-add-target="#payment_particular_{{ $index }}" required>
                                <option value="">Select Particulars</option>
                                <optgroup label="Quick Actions">
                                    <option value="__quick_add_party">+ Quick Add Party</option>
                                    <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                                </optgroup>
                                @foreach(collect($particularsOptions ?? [])->groupBy('group') as $group => $options)
                                    <optgroup label="{{ $group }}">
                                    @foreach($options as $option)
                                        <option value="{{ $option['id'] }}" {{ (string) ($row['account_id'] ?? '') === (string) $option['id'] ? 'selected' : '' }}
                                            data-kind="{{ $option['kind'] ?? 'account' }}"
                                            data-party-balance="{{ $option['party_balance'] ?? '' }}"
                                            data-party-balance-type="{{ $option['party_balance_type'] ?? '' }}">{{ $option['text'] }}</option>
                                    @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1 pr-balance-hint"></small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control pr-amount" name="payment_rows[{{ $index }}][amount]" value="{{ $row['amount'] ?? '' }}" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger remove-payment-receipt-row" {{ count($paymentRows) <= 1 ? 'style=display:none;' : '' }}>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="payment-receipt-row row g-2 mb-2" data-index="0">
                        <div class="col-md-8">
                            <label class="form-label">Particulars <span class="text-danger">*</span></label>
                            <select class="form-select pr-particular" id="payment_particular_0" name="payment_rows[0][account_id]" data-quick-add-value-mode="token" data-quick-add-in-select="1" data-quick-add-party-type="{{ $voucher->voucher_type === 'payment' ? 'creditor' : 'debtor' }}" data-quick-add-target="#payment_particular_0" required>
                                <option value="">Select Particulars</option>
                                <optgroup label="Quick Actions">
                                    <option value="__quick_add_party">+ Quick Add Party</option>
                                    <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                                </optgroup>
                                @foreach(collect($particularsOptions ?? [])->groupBy('group') as $group => $options)
                                    <optgroup label="{{ $group }}">
                                    @foreach($options as $option)
                                        <option value="{{ $option['id'] }}"
                                            data-kind="{{ $option['kind'] ?? 'account' }}"
                                            data-party-balance="{{ $option['party_balance'] ?? '' }}"
                                            data-party-balance-type="{{ $option['party_balance_type'] ?? '' }}">{{ $option['text'] }}</option>
                                    @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1 pr-balance-hint"></small>
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
                    @endforelse
                </div>
                <div class="mb-3">
                    <button type="button" id="addPaymentReceiptRow" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add Particulars
                    </button>
                    <small class="text-muted ms-2 d-block mt-2">
                        @if($voucher->voucher_type === 'payment')
                            Payment (Tally style): Dr Particulars, Cr Cash/Bank — posting is automatic.
                        @else
                            Receipt (Tally style): Dr Cash/Bank, Cr Particulars — posting is automatic.
                        @endif
                    </small>
                </div>
            @elseif($isAdjustment)
                <div id="adjustmentRows" class="mb-3">
                    @forelse($adjustmentRows as $index => $row)
                    <div class="adjustment-row row g-2 mb-2" data-index="{{ $index }}">
                        <div class="col-md-5">
                            <label class="form-label">Particulars (Party / Ledger) <span class="text-danger">*</span></label>
                            <select class="form-select adjustment-particular" id="adjustment_particular_{{ $index }}" name="adjustment_rows[{{ $index }}][account_id]" data-quick-add-value-mode="token" data-quick-add-in-select="1" data-quick-add-party-type="debtor" data-quick-add-target="#adjustment_particular_{{ $index }}" required>
                                <option value="">Select Party / Ledger</option>
                                <optgroup label="Quick Actions">
                                    <option value="__quick_add_party">+ Quick Add Party</option>
                                    <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                                </optgroup>
                                @foreach(collect($accounts)->groupBy(fn ($option) => $option['group'] ?? 'Ledger Accounts') as $group => $options)
                                    <optgroup label="{{ $group }}">
                                    @foreach($options as $account)
                                        <option value="{{ $account['id'] }}" {{ (string) ($row['account_id'] ?? '') === (string) $account['id'] ? 'selected' : '' }}>{{ $account['text'] }}</option>
                                    @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Dr / Cr <span class="text-danger">*</span></label>
                            <select class="form-select adjustment-entry-type" name="adjustment_rows[{{ $index }}][entry_type]" required>
                                <option value="">Select</option>
                                <option value="debit" {{ ($row['entry_type'] ?? '') === 'debit' ? 'selected' : '' }}>Debit</option>
                                <option value="credit" {{ ($row['entry_type'] ?? '') === 'credit' ? 'selected' : '' }}>Credit</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control adjustment-amount" name="adjustment_rows[{{ $index }}][amount]" value="{{ $row['amount'] ?? '' }}" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger remove-adjustment-row" {{ count($adjustmentRows) <= 2 ? 'style=display:none;' : '' }}>
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="adjustment-row row g-2 mb-2" data-index="0">
                        <div class="col-md-5">
                            <label class="form-label">Particulars (Party / Ledger) <span class="text-danger">*</span></label>
                            <select class="form-select adjustment-particular" id="adjustment_particular_0" name="adjustment_rows[0][account_id]" data-quick-add-value-mode="token" data-quick-add-in-select="1" data-quick-add-party-type="debtor" data-quick-add-target="#adjustment_particular_0" required>
                                <option value="">Select Party / Ledger</option>
                                <optgroup label="Quick Actions">
                                    <option value="__quick_add_party">+ Quick Add Party</option>
                                    <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                                </optgroup>
                                @foreach(collect($accounts)->groupBy(fn ($option) => $option['group'] ?? 'Ledger Accounts') as $group => $options)
                                    <optgroup label="{{ $group }}">
                                    @foreach($options as $account)
                                        <option value="{{ $account['id'] }}">{{ $account['text'] }}</option>
                                    @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Dr / Cr <span class="text-danger">*</span></label>
                            <select class="form-select adjustment-entry-type" name="adjustment_rows[0][entry_type]" required>
                                <option value="">Select</option>
                                <option value="debit" selected>Debit</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control adjustment-amount" name="adjustment_rows[0][amount]" value="" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger remove-adjustment-row" style="display:none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    <div class="adjustment-row row g-2 mb-2" data-index="1">
                        <div class="col-md-5">
                            <label class="form-label">Particulars (Party / Ledger) <span class="text-danger">*</span></label>
                            <select class="form-select adjustment-particular" id="adjustment_particular_1" name="adjustment_rows[1][account_id]" data-quick-add-value-mode="token" data-quick-add-in-select="1" data-quick-add-party-type="debtor" data-quick-add-target="#adjustment_particular_1" required>
                                <option value="">Select Party / Ledger</option>
                                <optgroup label="Quick Actions">
                                    <option value="__quick_add_party">+ Quick Add Party</option>
                                    <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                                </optgroup>
                                @foreach(collect($accounts)->groupBy(fn ($option) => $option['group'] ?? 'Ledger Accounts') as $group => $options)
                                    <optgroup label="{{ $group }}">
                                    @foreach($options as $account)
                                        <option value="{{ $account['id'] }}">{{ $account['text'] }}</option>
                                    @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Dr / Cr <span class="text-danger">*</span></label>
                            <select class="form-select adjustment-entry-type" name="adjustment_rows[1][entry_type]" required>
                                <option value="">Select</option>
                                <option value="debit">Debit</option>
                                <option value="credit" selected>Credit</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control adjustment-amount" name="adjustment_rows[1][amount]" value="" step="0.01" min="0.01" placeholder="0.00" required>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-danger remove-adjustment-row" style="display:none;">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    @endforelse
                </div>
                <div class="mb-3">
                    <button type="button" id="addAdjustmentRow" class="btn btn-outline-primary">
                        <i class="bi bi-plus-circle me-2"></i>Add Line
                    </button>
                    <small class="text-muted ms-2 d-block mt-2">
                        Journal (Tally style): add debit and credit lines. Total Debit must equal Total Credit — posting is automatic.
                    </small>
                </div>
            @else
                <div id="voucherLines">
                    @foreach($voucherLines as $index => $line)
                    <div class="voucher-line row mb-3" data-index="{{ $index }}">
                        <div class="col-md-4">
                            <label class="form-label">Account <span class="text-danger">*</span></label>
                            <select class="form-select line-account" id="voucher_line_account_{{ $index }}" name="lines[{{ $index }}][account_id]" data-quick-add-in-select="1" data-quick-add-target="#voucher_line_account_{{ $index }}" required>
                                <option value="">Select Account</option>
                                <optgroup label="Quick Actions">
                                    <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                                </optgroup>
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
    const isPaymentReceipt = @json($isPaymentReceipt);
    const isAdjustment = @json($isAdjustment);
    const cashBankAccounts = @json($cashBankAccounts ?? []);
    const particularsOptions = @json($particularsOptions ?? []);
    const accounts = @json($accounts);
    let lineIndex = {{ count($voucherLines) }};
    let paymentReceiptRowIndex = {{ max(1, count($paymentRows)) }};
    let adjustmentRowIndex = {{ max(2, count($adjustmentRows)) }};

    function cashBankOptionsHtml(selectedValue = '') {
        let html = '<option value="">Select Cash / Bank / OD</option>';
        cashBankAccounts.forEach((option) => {
            const selected = String(selectedValue) === String(option.id) ? 'selected' : '';
            html += `<option value="${option.id}" ${selected}>${option.text}</option>`;
        });
        return html;
    }

    function particularsOptionsHtml(selectedValue = '') {
        let html = '<option value="">Select Particulars</option>';
        const groups = {};
        particularsOptions.forEach((option) => {
            const group = option.group || 'Ledger Accounts';
            groups[group] = groups[group] || [];
            groups[group].push(option);
        });
        Object.entries(groups).forEach(([group, options]) => {
            html += `<optgroup label="${group}">`;
            options.forEach((option) => {
                const selected = String(selectedValue) === String(option.id) ? 'selected' : '';
                const kind = option.kind || 'account';
                const partyBalance = option.party_balance ?? '';
                const partyBalanceType = option.party_balance_type || '';
                html += `<option value="${option.id}" ${selected} data-kind="${kind}" data-party-balance="${partyBalance}" data-party-balance-type="${partyBalanceType}">${option.text}</option>`;
            });
            html += '</optgroup>';
        });
        return html;
    }

    function accountOptionsHtml(selectedValue = '') {
        let html = '<option value="">Select Account</option>';
        const groups = {};
        accounts.forEach((account) => {
            const group = account.group || 'Ledger Accounts';
            groups[group] = groups[group] || [];
            groups[group].push(account);
        });
        Object.entries(groups).forEach(([group, options]) => {
            html += `<optgroup label="${group}">`;
            options.forEach((account) => {
                const selected = String(selectedValue) === String(account.id) ? 'selected' : '';
                html += `<option value="${account.id}" ${selected}>${account.text}</option>`;
            });
            html += '</optgroup>';
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
                const entryType = $(this).find('.adjustment-entry-type').val();
                const amount = parseFloat($(this).find('.adjustment-amount').val()) || 0;
                if (entryType === 'debit') {
                    totalDebit += amount;
                } else if (entryType === 'credit') {
                    totalCredit += amount;
                }
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
            const selected = $('#cash_bank_account_id').data('selected') || $('#cash_bank_account_id').val() || '';
            $('#cash_bank_account_id').html(cashBankOptionsHtml(selected));
            $('#cash_bank_account_id').removeData('selected');
        }

        function buildPaymentReceiptRow(index) {
            return $(`
                <div class="payment-receipt-row row g-2 mb-2" data-index="${index}">
                    <div class="col-md-8">
                        <select class="form-select pr-particular" id="payment_particular_${index}" name="payment_rows[${index}][account_id]" data-quick-add-value-mode="token" data-quick-add-in-select="1" data-quick-add-party-type="${voucherType === 'payment' ? 'creditor' : 'debtor'}" data-quick-add-target="#payment_particular_${index}" required>
                            <option value="">Select Particulars</option>
                            <optgroup label="Quick Actions">
                                <option value="__quick_add_party">+ Quick Add Party</option>
                                <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                            </optgroup>
                            ${particularsOptionsHtml()}
                        </select>
                        <small class="text-muted d-block mt-1 pr-balance-hint"></small>
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

        function updateParticularBalanceHint($select) {
            const $hint = $select.closest('.col-md-8').find('.pr-balance-hint');
            if (!$hint.length) {
                return;
            }

            const $selected = $select.find('option:selected');
            const kind = String($selected.data('kind') || '');
            const rawBalance = $selected.data('party-balance');
            const balanceType = String($selected.data('party-balance-type') || '');

            if (kind !== 'party' || rawBalance === undefined || rawBalance === null || rawBalance === '') {
                $hint.text('');
                return;
            }

            const balance = parseFloat(rawBalance) || 0;
            const suffix = balanceType === 'credit' ? 'Cr' : 'Dr';
            $hint.text(`Party balance: ₹${balance.toFixed(2)} ${suffix}`);
        }

        $('#addPaymentReceiptRow').on('click', function() {
            const row = buildPaymentReceiptRow(paymentReceiptRowIndex);
            $('#paymentReceiptRows').append(row);
            initSearchableSelects(row);
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

        $(document).on('input', '.payment-receipt-row .pr-amount', calculateTotals);

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
            updateParticularBalanceHint($select);
        });

        refreshCashBankDropdown();
        updatePaymentReceiptRemoveButtons();
        refreshParticularsAvailability();
        $('.pr-particular').each(function() {
            updateParticularBalanceHint($(this));
        });
    } else if (isAdjustment) {
        function updateAdjustmentRemoveButtons() {
            if ($('.adjustment-row').length <= 2) {
                $('.remove-adjustment-row').hide();
            } else {
                $('.remove-adjustment-row').show();
            }
        }

        function buildAdjustmentRow(index, selectedAccountId = '', selectedEntryType = '', selectedAmount = '') {
            return $(`
                <div class="adjustment-row row g-2 mb-2" data-index="${index}">
                    <div class="col-md-5">
                        <select class="form-select adjustment-particular" id="adjustment_particular_${index}" name="adjustment_rows[${index}][account_id]" data-quick-add-value-mode="token" data-quick-add-in-select="1" data-quick-add-party-type="debtor" data-quick-add-target="#adjustment_particular_${index}" required>
                            <option value="">Select Party / Ledger</option>
                            <optgroup label="Quick Actions">
                                <option value="__quick_add_party">+ Quick Add Party</option>
                                <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                            </optgroup>
                            ${accountOptionsHtml(selectedAccountId)}
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select class="form-select adjustment-entry-type" name="adjustment_rows[${index}][entry_type]" required>
                            <option value="">Select</option>
                            <option value="debit" ${selectedEntryType === 'debit' ? 'selected' : ''}>Debit</option>
                            <option value="credit" ${selectedEntryType === 'credit' ? 'selected' : ''}>Credit</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control adjustment-amount" name="adjustment_rows[${index}][amount]" value="${selectedAmount}" step="0.01" min="0.01" placeholder="0.00" required>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger remove-adjustment-row">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            `);
        }

        $('#addAdjustmentRow').on('click', function() {
            const row = buildAdjustmentRow(adjustmentRowIndex);
            $('#adjustmentRows').append(row);
            initSearchableSelects(row);
            adjustmentRowIndex++;
            updateAdjustmentRemoveButtons();
        });

        $(document).on('click', '.remove-adjustment-row', function() {
            if ($('.adjustment-row').length <= 2) {
                toastr.error('At least two journal lines are required.');
                return;
            }
            $(this).closest('.adjustment-row').remove();
            updateAdjustmentRemoveButtons();
            calculateTotals();
        });

        $(document).on('input change', '.adjustment-entry-type, .adjustment-amount', calculateTotals);

        updateAdjustmentRemoveButtons();
    } else {
        $('#addLine').on('click', function() {
            const newLine = `
                <div class="voucher-line row mb-3" data-index="${lineIndex}">
                    <div class="col-md-4">
                        <select class="form-select line-account" id="voucher_line_account_${lineIndex}" name="lines[${lineIndex}][account_id]" data-quick-add-in-select="1" data-quick-add-target="#voucher_line_account_${lineIndex}" required>
                            <option value="">Select Account</option>
                            <optgroup label="Quick Actions">
                                <option value="__quick_add_ledger">+ Quick Add Cash / Bank Ledger</option>
                            </optgroup>
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
            initSearchableSelects($('#voucherLines .voucher-line').last());
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

    ajaxFormSubmit('voucherForm', '{{ route("admin.vouchers.update", $voucher->id) }}', 'PUT', '{{ route("admin.vouchers.type", $voucher->voucher_type) }}');
    initSearchableSelects($('#voucherForm'));
    calculateTotals();
});
</script>
@endpush
