<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use App\Models\Account;
use App\Models\Party;
use App\Services\LedgerService;
use Illuminate\Validation\Rule;

class VoucherRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'voucher_type' => ['required', Rule::in(['income', 'expense', 'receipt', 'payment', 'journal', 'adjustment'])],
            'voucher_date' => 'required|date',
            'party_id' => 'nullable|exists:parties,id',
            'payment_mode' => ['required_if:voucher_type,payment,receipt', Rule::in(['cash', 'bank', 'od'])],
            'cash_bank_account_id' => ['required_if:voucher_type,payment,receipt', 'nullable', 'exists:accounts,id'],
            'narration' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:500',

            'payment_rows' => 'required_if:voucher_type,payment,receipt|array|min:1',
            'payment_rows.*.account_id' => 'required_if:voucher_type,payment,receipt|exists:accounts,id',
            'payment_rows.*.amount' => 'required_if:voucher_type,payment,receipt|numeric|gt:0',

            'adjustment_rows' => 'required_if:voucher_type,journal,adjustment|array|min:1',
            'adjustment_rows.*.creditor_account_id' => 'required_if:voucher_type,journal,adjustment|exists:accounts,id',
            'adjustment_rows.*.credit_amount' => 'nullable|numeric|min:0',
            'adjustment_rows.*.debitor_account_id' => 'required_if:voucher_type,journal,adjustment|exists:accounts,id',
            'adjustment_rows.*.debit_amount' => 'nullable|numeric|min:0',

            'lines' => 'required|array|min:1',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'voucher_type.required' => 'Voucher type is required',
            'voucher_type.in' => 'Invalid voucher type',
            'voucher_date.required' => 'Voucher date is required',
            'cash_bank_account_id.required_if' => 'Cash / Bank account is required',
            'payment_rows.required_if' => 'At least one particulars row is required',
            'payment_rows.*.account_id.required_if' => 'Particulars account is required',
            'payment_rows.*.amount.required_if' => 'Amount is required',
            'payment_rows.*.amount.gt' => 'Amount must be greater than zero',
            'adjustment_rows.required_if' => 'At least one adjustment row is required',
            'adjustment_rows.*.creditor_account_id.required_if' => 'Creditor account is required',
            'adjustment_rows.*.debitor_account_id.required_if' => 'Debitor account is required',
            'lines.required' => 'At least one voucher line is required',
        ];
    }

    protected function prepareForValidation(): void
    {
        $voucherType = $this->input('voucher_type');

        if (in_array($voucherType, ['payment', 'receipt'], true) && $this->has('payment_rows')) {
            $rows = (array) $this->input('payment_rows');
            $cashBankAccountId = (int) $this->input('cash_bank_account_id');

            $this->merge([
                'lines' => $this->normalizePaymentReceiptRows($rows, $cashBankAccountId, $voucherType),
                'party_id' => $this->input('party_id') ?: $this->resolvePartyIdFromPaymentRows($rows),
            ]);
            return;
        }

        if (in_array($voucherType, ['journal', 'adjustment'], true) && $this->has('adjustment_rows')) {
            $this->merge([
                'lines' => $this->normalizeAdjustmentRows((array) $this->input('adjustment_rows')),
            ]);
            return;
        }

        if ($this->has('lines')) {
            $lines = collect($this->lines)->map(function ($line) {
                return [
                    'account_id' => $line['account_id'] ?? null,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'description' => $line['description'] ?? null,
                ];
            })->toArray();

            $this->merge(['lines' => $lines]);
        }
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $voucherType = $this->input('voucher_type');

            if (in_array($voucherType, ['payment', 'receipt'], true)) {
                $cashBankAccountId = (int) $this->input('cash_bank_account_id');
                $cashBank = Account::find($cashBankAccountId);

                if ($cashBankAccountId && $cashBank) {
                    $mode = $this->input('payment_mode');
                    if ($mode && $cashBank->transaction_mode !== $mode) {
                        $validator->errors()->add('cash_bank_account_id', 'Selected account must match the payment mode.');
                    }

                    if (!in_array($cashBank->transaction_mode, ['cash', 'bank', 'od'], true)) {
                        $validator->errors()->add('cash_bank_account_id', 'Select a Cash / Bank / OD account.');
                    }
                }

                $seenParticularAccountIds = [];
                foreach ((array) $this->input('payment_rows', []) as $index => $row) {
                    $particularAccountId = (int) ($row['account_id'] ?? 0);

                    if ($particularAccountId === $cashBankAccountId) {
                        $validator->errors()->add(
                            "payment_rows.{$index}.account_id",
                            'Particulars cannot be the same as Cash / Bank account.'
                        );
                    }

                    if ($particularAccountId > 0) {
                        if (in_array($particularAccountId, $seenParticularAccountIds, true)) {
                            $validator->errors()->add(
                                "payment_rows.{$index}.account_id",
                                'Same particulars cannot be selected in more than one row. Combine the amount in a single row.'
                            );
                        } else {
                            $seenParticularAccountIds[] = $particularAccountId;
                        }
                    }
                }

                if ($voucherType === 'payment' && $cashBank && $cashBankAccountId) {
                    $this->validatePaymentBalance($validator, $cashBank, $cashBankAccountId);
                }
            }

            if (in_array($voucherType, ['journal', 'adjustment'], true) && is_array($this->input('adjustment_rows'))) {
                foreach ((array) $this->input('adjustment_rows') as $index => $row) {
                    if (($row['creditor_account_id'] ?? null) == ($row['debitor_account_id'] ?? null)) {
                        $validator->errors()->add(
                            "adjustment_rows.{$index}.debitor_account_id",
                            'Creditor and Debitor accounts cannot be the same.'
                        );
                    }
                }

                $this->validateAdjustmentRowAmounts($validator, (array) $this->input('adjustment_rows'), 'adjustment_rows');
            }

            if ($this->has('lines') && is_array($this->lines)) {
                $totalDebit = 0;
                $totalCredit = 0;

                foreach ($this->lines as $line) {
                    $totalDebit += $line['debit'] ?? 0;
                    $totalCredit += $line['credit'] ?? 0;
                }

                if (abs($totalDebit - $totalCredit) > 0.01) {
                    $validator->errors()->add(
                        'lines',
                        'Voucher must be balanced. Total debit must equal total credit.'
                    );
                }
            }
        });
    }

    protected function validatePaymentBalance($validator, Account $cashBank, int $cashBankAccountId): void
    {
        if ($cashBank->transaction_mode === 'od') {
            return;
        }

        $totalPayment = 0.0;
        foreach ((array) $this->input('payment_rows', []) as $row) {
            $totalPayment += (float) ($row['amount'] ?? 0);
        }

        if ($totalPayment <= 0) {
            return;
        }

        $companyId = (int) ($this->user()?->company_id ?? 0);
        $financialYearId = (int) ($this->user()?->company?->currentFinancialYear?->id ?? 0);

        $available = app(LedgerService::class)->getAvailablePaymentBalance(
            $cashBankAccountId,
            $companyId,
            $financialYearId
        );

        if ($available !== null && $totalPayment > $available + 0.009) {
            $validator->errors()->add(
                'cash_bank_account_id',
                'Insufficient balance in ' . $cashBank->account_name . '. Available: ₹' . number_format($available, 2)
            );
        }
    }

    protected function validateAdjustmentRowAmounts($validator, array $rows, string $prefix): void
    {
        foreach ($rows as $index => $row) {
            $debit = (float) ($row['debit_amount'] ?? 0);
            $credit = (float) ($row['credit_amount'] ?? 0);

            if ($debit <= 0) {
                $validator->errors()->add("{$prefix}.{$index}.debit_amount", 'Debit amount is required.');
            }

            if ($credit <= 0) {
                $validator->errors()->add("{$prefix}.{$index}.credit_amount", 'Credit amount is required.');
            }

            if ($debit > 0 && $credit > 0 && abs($debit - $credit) > 0.01) {
                $validator->errors()->add("{$prefix}.{$index}.debit_amount", 'Debit and Credit amounts must be equal.');
            }
        }
    }

    protected function resolvePartyIdFromPaymentRows(array $rows): ?int
    {
        $partyByAccount = Party::where('company_id', $this->user()?->company_id)
            ->whereNotNull('account_id')
            ->pluck('id', 'account_id');

        foreach ($rows as $row) {
            $accountId = $row['account_id'] ?? null;
            if ($accountId && isset($partyByAccount[$accountId])) {
                return (int) $partyByAccount[$accountId];
            }
        }

        return null;
    }

    /**
     * CA style:
     * Payment  => Debit Particulars, Credit Cash/Bank
     * Receipt  => Debit Cash/Bank, Credit Particulars
     */
    protected function normalizePaymentReceiptRows(array $rows, int $cashBankAccountId, string $voucherType): array
    {
        $lines = [];
        $totalAmount = 0;

        foreach ($rows as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            $accountId = (int) ($row['account_id'] ?? 0);

            if ($amount <= 0 || $accountId <= 0) {
                continue;
            }

            $totalAmount += $amount;

            if ($voucherType === 'payment') {
                // Money going out: Dr Party/Expense, Cr Cash/Bank
                $lines[] = [
                    'account_id' => $accountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => $row['description'] ?? null,
                ];
            } else {
                // Money coming in: Dr Cash/Bank (later), Cr Party/Income
                $lines[] = [
                    'account_id' => $accountId,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => $row['description'] ?? null,
                ];
            }
        }

        if ($totalAmount > 0 && $cashBankAccountId > 0) {
            if ($voucherType === 'payment') {
                $lines[] = [
                    'account_id' => $cashBankAccountId,
                    'debit' => 0,
                    'credit' => $totalAmount,
                    'description' => null,
                ];
            } else {
                // Receipt: Cash/Bank is debited with total
                array_unshift($lines, [
                    'account_id' => $cashBankAccountId,
                    'debit' => $totalAmount,
                    'credit' => 0,
                    'description' => null,
                ]);
            }
        }

        return $lines;
    }

    protected function normalizeAdjustmentRows(array $rows): array
    {
        $lines = [];

        foreach ($rows as $row) {
            $creditAmount = (float) ($row['credit_amount'] ?? 0);
            $debitAmount = (float) ($row['debit_amount'] ?? 0);

            $lines[] = [
                'account_id' => (int) ($row['creditor_account_id'] ?? 0),
                'debit' => 0,
                'credit' => $creditAmount,
                'description' => $row['description'] ?? null,
            ];

            $lines[] = [
                'account_id' => (int) ($row['debitor_account_id'] ?? 0),
                'debit' => $debitAmount,
                'credit' => 0,
                'description' => $row['description'] ?? null,
            ];
        }

        return $lines;
    }
}
