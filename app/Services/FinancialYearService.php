<?php

namespace App\Services;

use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FinancialYearService
{
    public function __construct(
        protected LedgerService $ledgerService,
        protected VoucherService $voucherService
    ) {
    }

    /**
     * When a FY becomes current, bring prior-year closing balances in as an opening journal.
     */
    public function setAsCurrent(FinancialYear $financialYear): FinancialYear
    {
        if ($financialYear->is_closed) {
            throw new \RuntimeException('Cannot set a closed financial year as current.');
        }

        return DB::transaction(function () use ($financialYear) {
            $financialYear->setAsCurrent();
            $this->carryForwardOpeningBalances($financialYear->fresh());

            return $financialYear->fresh();
        });
    }

    /**
     * Carry prior FY closing balances into the new FY as a balanced opening journal.
     */
    public function carryForwardOpeningBalances(FinancialYear $targetFy): ?Voucher
    {
        if ($targetFy->is_closed) {
            throw new \RuntimeException('Cannot carry forward into a closed financial year.');
        }

        $alreadyCarried = Voucher::query()
            ->where('company_id', $targetFy->company_id)
            ->where('financial_year_id', $targetFy->id)
            ->where('voucher_type', 'journal')
            ->where('status', 'posted')
            ->where('narration', 'like', 'Opening balances carried forward%')
            ->exists();

        if ($alreadyCarried) {
            return null;
        }

        $previousFy = FinancialYear::query()
            ->where('company_id', $targetFy->company_id)
            ->where('id', '!=', $targetFy->id)
            ->whereDate('end_date', '<', $targetFy->start_date->format('Y-m-d'))
            ->orderByDesc('end_date')
            ->first();

        if (!$previousFy) {
            return null;
        }

        $accounts = Account::query()
            ->where('company_id', $targetFy->company_id)
            ->orderBy('account_code')
            ->get();

        $lines = [];
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($accounts as $account) {
            if ($account->account_code === Account::CODE_SUSPENSE) {
                continue;
            }

            $closing = $this->ledgerService->getAccountBalance(
                $account->id,
                $targetFy->company_id,
                $previousFy->id
            );

            $amount = round((float) $closing['balance'], 2);
            if ($amount < 0.01) {
                continue;
            }

            if ($closing['type'] === 'debit') {
                $lines[] = [
                    'account_id' => $account->id,
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => "Opening from {$previousFy->name}",
                ];
                $totalDebit += $amount;
            } else {
                $lines[] = [
                    'account_id' => $account->id,
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => "Opening from {$previousFy->name}",
                ];
                $totalCredit += $amount;
            }
        }

        $difference = round($totalDebit - $totalCredit, 2);
        if (abs($difference) >= 0.01) {
            $suspense = $this->ledgerService->ensureSystemAccount(
                Account::CODE_SUSPENSE,
                $targetFy->company_id,
                $targetFy->id,
                null,
                null
            );

            if ($difference > 0) {
                $lines[] = [
                    'account_id' => $suspense->id,
                    'debit' => 0,
                    'credit' => abs($difference),
                    'description' => "Opening difference from {$previousFy->name}",
                ];
            } else {
                $lines[] = [
                    'account_id' => $suspense->id,
                    'debit' => abs($difference),
                    'credit' => 0,
                    'description' => "Opening difference from {$previousFy->name}",
                ];
            }
        }

        if ($lines === []) {
            return null;
        }

        return DB::transaction(function () use ($targetFy, $previousFy, $lines) {
            $voucher = $this->voucherService->create([
                'uuid' => (string) Str::uuid(),
                'company_id' => $targetFy->company_id,
                'financial_year_id' => $targetFy->id,
                'voucher_type' => 'journal',
                'voucher_date' => $targetFy->start_date->format('Y-m-d'),
                'narration' => "Opening balances carried forward from {$previousFy->name}",
                'remarks' => 'System FY opening carry-forward',
                'lines' => $lines,
            ]);

            \App\Models\Ledger::where('voucher_id', $voucher->id)->update([
                'reference_type' => 'fy_opening_balance',
                'reference_id' => $targetFy->id,
            ]);

            foreach ($voucher->lines as $line) {
                $this->ledgerService->recalculateBalances(
                    (int) $line->account_id,
                    (int) $targetFy->company_id,
                    (int) $targetFy->id
                );
            }

            return $voucher->fresh(['lines.account']);
        });
    }
}
