<?php

namespace App\Http\Requests\Concerns;

use App\Models\Account;
use App\Models\Party;
use App\Services\LedgerService;
use Illuminate\Validation\Rule;

trait ValidatesVoucherPayload
{
    public function voucherRules(): array
    {
        $companyId = $this->user()?->company_id;
        $companyAccount = Rule::exists('accounts', 'id')
            ->where(fn ($query) => $query->where('company_id', $companyId)->where('is_active', true));
        $companyParty = Rule::exists('parties', 'id')
            ->where(fn ($query) => $query->where('company_id', $companyId)->where('is_active', true));

        return [
            'voucher_type' => ['required', Rule::in(['income', 'expense', 'receipt', 'payment', 'journal', 'adjustment'])],
            'voucher_date' => 'required|date',
            'party_id' => ['nullable', $companyParty],
            'payment_mode' => ['required_if:voucher_type,payment,receipt', Rule::in(['cash', 'bank', 'od'])],
            'cash_bank_account_id' => ['required_if:voucher_type,payment,receipt', 'nullable', $companyAccount],
            'narration' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:500',

            'payment_rows' => 'required_if:voucher_type,payment,receipt|array|min:1',
            'payment_rows.*.account_id' => ['required_if:voucher_type,payment,receipt', $companyAccount],
            'payment_rows.*.amount' => 'required_if:voucher_type,payment,receipt|numeric|gt:0',
            'payment_rows.*.description' => 'nullable|string|max:255',

            'adjustment_rows' => 'required_if:voucher_type,journal,adjustment|array|min:2',
            'adjustment_rows.*.account_id' => ['required_if:voucher_type,journal,adjustment', $companyAccount],
            'adjustment_rows.*.entry_type' => ['required_if:voucher_type,journal,adjustment', Rule::in(['debit', 'credit'])],
            'adjustment_rows.*.amount' => 'required_if:voucher_type,journal,adjustment|numeric|gt:0',
            'adjustment_rows.*.description' => 'nullable|string|max:255',

            'lines' => 'required|array|min:1',
            'lines.*.account_id' => ['required', $companyAccount],
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ];
    }

    public function voucherMessages(): array
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
            'adjustment_rows.required_if' => 'At least two journal lines are required',
            'adjustment_rows.min' => 'At least two journal lines are required',
            'adjustment_rows.*.account_id.required_if' => 'Particulars account is required',
            'adjustment_rows.*.entry_type.required_if' => 'Dr / Cr is required',
            'adjustment_rows.*.entry_type.in' => 'Each line must be either Debit or Credit',
            'adjustment_rows.*.amount.required_if' => 'Amount is required',
            'adjustment_rows.*.amount.gt' => 'Amount must be greater than zero',
            'lines.required' => 'At least one voucher line is required',
        ];
    }

    protected function prepareVoucherPayload(): void
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
            $rows = (array) $this->input('adjustment_rows');
            $this->merge([
                'lines' => $this->normalizeAdjustmentRows($rows),
                'party_id' => $this->input('party_id') ?: $this->resolvePartyIdFromAdjustmentRows($rows),
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

    protected function validateVoucherPayload($validator): void
    {
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
            $hasDebit = false;
            $hasCredit = false;

            foreach ((array) $this->input('adjustment_rows') as $row) {
                $entryType = $row['entry_type'] ?? '';
                $amount = (float) ($row['amount'] ?? 0);

                if ($entryType === 'debit' && $amount > 0) {
                    $hasDebit = true;
                }
                if ($entryType === 'credit' && $amount > 0) {
                    $hasCredit = true;
                }
            }

            if (!$hasDebit || !$hasCredit) {
                $validator->errors()->add(
                    'adjustment_rows',
                    'Journal voucher must have at least one Debit line and one Credit line.'
                );
            }
        }

        if ($this->has('lines') && is_array($this->lines)) {
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($this->lines as $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);
                $totalDebit += $debit;
                $totalCredit += $credit;

                if (($debit > 0 && $credit > 0) || ($debit <= 0 && $credit <= 0)) {
                    $validator->errors()->add(
                        'lines',
                        'Each voucher line must contain either a debit or a credit amount, but not both.'
                    );
                }
            }

            if (abs($totalDebit - $totalCredit) > 0.01) {
                $validator->errors()->add(
                    'lines',
                    'Voucher must be balanced. Total debit must equal total credit.'
                );
            }
        }
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

    protected function resolvePartyIdFromAdjustmentRows(array $rows): ?int
    {
        $accountIds = collect($rows)
            ->pluck('account_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique();

        return Party::where('company_id', $this->user()?->company_id)
            ->whereIn('account_id', $accountIds)
            ->value('id');
    }

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
                $lines[] = [
                    'account_id' => $accountId,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => $row['description'] ?? null,
                ];
            } else {
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
            $amount = (float) ($row['amount'] ?? 0);
            $accountId = (int) ($row['account_id'] ?? 0);
            $entryType = $row['entry_type'] ?? '';

            if ($amount <= 0 || $accountId <= 0 || !in_array($entryType, ['debit', 'credit'], true)) {
                continue;
            }

            $lines[] = [
                'account_id' => $accountId,
                'debit' => $entryType === 'debit' ? $amount : 0,
                'credit' => $entryType === 'credit' ? $amount : 0,
                'description' => $row['description'] ?? null,
            ];
        }

        return $lines;
    }
}
