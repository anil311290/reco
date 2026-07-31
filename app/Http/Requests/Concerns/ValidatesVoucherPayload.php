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
            'voucher_type' => ['required', Rule::in(['receipt', 'payment', 'journal', 'adjustment'])],
            'voucher_date' => 'required|date',
            'party_id' => ['nullable', $companyParty],
            'cash_bank_account_id' => ['required_if:voucher_type,payment,receipt', 'nullable', $companyAccount],
            'narration' => 'nullable|string|max:500',

            'payment_rows' => 'required_if:voucher_type,payment,receipt|array|min:1',
            'payment_rows.*.account_id' => ['required_if:voucher_type,payment,receipt', 'string'],
            'payment_rows.*.amount' => 'required_if:voucher_type,payment,receipt|numeric|gt:0',
            'payment_rows.*.description' => 'nullable|string|max:255',

            'adjustment_rows' => 'required_if:voucher_type,journal,adjustment|array|min:2',
            'adjustment_rows.*.account_id' => ['required_if:voucher_type,journal,adjustment', 'string'],
            'adjustment_rows.*.entry_type' => ['required_if:voucher_type,journal,adjustment', Rule::in(['debit', 'credit'])],
            'adjustment_rows.*.amount' => 'required_if:voucher_type,journal,adjustment|numeric|gt:0',
            'adjustment_rows.*.description' => 'nullable|string|max:255',

            'lines' => 'required|array|min:1',
            'lines.*.account_id' => ['required', $companyAccount],
            'lines.*.party_id' => ['nullable', $companyParty],
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ];
    }

    public function voucherMessages(): array
    {
        return [
            'voucher_type.required' => 'Voucher type is required',
            'voucher_type.in' => 'Invalid voucher type. Use sales/purchase invoices for income and expense.',
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
                    'party_id' => $line['party_id'] ?? null,
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
                if (!$cashBank->isCashBankOd()) {
                    $validator->errors()->add('cash_bank_account_id', 'Select a Cash / Bank / OD account.');
                }
            }

            $seenParticulars = [];
            foreach ((array) $this->input('payment_rows', []) as $index => $row) {
                $token = (string) ($row['account_id'] ?? '');

                if ($token === '') {
                    continue;
                }

                $resolved = $this->resolveParticular($token);

                if (!$resolved['account_id']) {
                    $validator->errors()->add(
                        "payment_rows.{$index}.account_id",
                        'Selected particulars is invalid.'
                    );
                    continue;
                }

                if ($resolved['account_id'] === $cashBankAccountId) {
                    $validator->errors()->add(
                        "payment_rows.{$index}.account_id",
                        'Particulars cannot be the same as Cash / Bank account.'
                    );
                }

                if (in_array($token, $seenParticulars, true)) {
                    $validator->errors()->add(
                        "payment_rows.{$index}.account_id",
                        'Same particulars cannot be selected in more than one row. Combine the amount in a single row.'
                    );
                } else {
                    $seenParticulars[] = $token;
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
        foreach ($rows as $row) {
            $resolved = $this->resolveParticular((string) ($row['account_id'] ?? ''));
            if ($resolved['party_id']) {
                return $resolved['party_id'];
            }
        }

        return null;
    }

    protected function resolvePartyIdFromAdjustmentRows(array $rows): ?int
    {
        foreach ($rows as $row) {
            $resolved = $this->resolveParticular((string) ($row['account_id'] ?? ''));
            if ($resolved['party_id']) {
                return $resolved['party_id'];
            }
        }

        return null;
    }

    /**
     * Resolve a particulars token into a posting account + optional party.
     * Party tokens ("party:{id}") post to the shared AR/AP control account and
     * carry the party_id; numeric tokens are a direct ledger account.
     *
     * @return array{account_id: ?int, party_id: ?int}
     */
    protected function resolveParticular(string $token): array
    {
        $token = trim($token);

        if (str_starts_with($token, 'party:')) {
            $partyId = (int) substr($token, 6);
            $party = Party::where('company_id', $this->user()?->company_id)->find($partyId);

            if (!$party) {
                return ['account_id' => null, 'party_id' => null];
            }

            $accountId = $party->account_id ?: $this->resolveControlAccountId($party->type);

            return [
                'account_id' => $accountId ? (int) $accountId : null,
                'party_id' => $partyId,
            ];
        }

        $accountId = (int) $token;

        return [
            'account_id' => $accountId > 0 ? $accountId : null,
            'party_id' => null,
        ];
    }

    protected function resolveControlAccountId(string $partyType): ?int
    {
        $code = $partyType === 'creditor' ? Account::CODE_AP : Account::CODE_AR;

        $id = Account::where('company_id', $this->user()?->company_id)
            ->where('account_code', $code)
            ->value('id');

        return $id ? (int) $id : null;
    }

    protected function normalizePaymentReceiptRows(array $rows, int $cashBankAccountId, string $voucherType): array
    {
        $lines = [];
        $totalAmount = 0;

        foreach ($rows as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            $resolved = $this->resolveParticular((string) ($row['account_id'] ?? ''));

            if ($amount <= 0 || !$resolved['account_id']) {
                continue;
            }

            $totalAmount += $amount;

            $lines[] = [
                'account_id' => $resolved['account_id'],
                'party_id' => $resolved['party_id'],
                'debit' => $voucherType === 'payment' ? $amount : 0,
                'credit' => $voucherType === 'payment' ? 0 : $amount,
                'description' => $row['description'] ?? null,
            ];
        }

        if ($totalAmount > 0 && $cashBankAccountId > 0) {
            $cashLine = [
                'account_id' => $cashBankAccountId,
                'party_id' => null,
                'debit' => $voucherType === 'payment' ? 0 : $totalAmount,
                'credit' => $voucherType === 'payment' ? $totalAmount : 0,
                'description' => null,
            ];

            if ($voucherType === 'payment') {
                $lines[] = $cashLine;
            } else {
                array_unshift($lines, $cashLine);
            }
        }

        return $lines;
    }

    protected function normalizeAdjustmentRows(array $rows): array
    {
        $lines = [];

        foreach ($rows as $row) {
            $amount = (float) ($row['amount'] ?? 0);
            $entryType = $row['entry_type'] ?? '';
            $resolved = $this->resolveParticular((string) ($row['account_id'] ?? ''));

            if ($amount <= 0 || !$resolved['account_id'] || !in_array($entryType, ['debit', 'credit'], true)) {
                continue;
            }

            $lines[] = [
                'account_id' => $resolved['account_id'],
                'party_id' => $resolved['party_id'],
                'debit' => $entryType === 'debit' ? $amount : 0,
                'credit' => $entryType === 'credit' ? $amount : 0,
                'description' => $row['description'] ?? null,
            ];
        }

        return $lines;
    }
}
