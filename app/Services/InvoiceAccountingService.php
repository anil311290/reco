<?php

namespace App\Services;

use App\Interfaces\AccountRepositoryInterface;
use App\Models\Account;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Setting;
use App\Models\TaxRate;
use Illuminate\Support\Str;

/**
 * Builds balanced double-entry voucher lines for sales & purchase invoices (Tally / CA style).
 *
 * Sales:  Dr Party (Sundry Debtor) = invoice total
 *        Cr Sales / Service income (per line ledger) = net taxable
 *        Cr Output tax (per tax rate) = tax
 *
 * Purchase: Dr Purchase / Expense (per line ledger) = net taxable
 *           Dr Input tax (per tax rate) = tax
 *           Cr Party (Sundry Creditor) = invoice total
 */
class InvoiceAccountingService
{
    public function __construct(
        protected AccountRepositoryInterface $accountRepository,
        protected SettingsService $settingsService
    ) {
    }

    /**
     * @return array<int, array{account_id:int,debit:float,credit:float,description:string}>
     */
    public function buildSalesVoucherLines(SalesInvoice $invoice, ?int $fallbackIncomeAccountId = null): array
    {
        $invoice->loadMissing(['lines.item', 'lines.taxRate', 'lines.account', 'party']);

        if ($invoice->lines->isEmpty()) {
            throw new \RuntimeException('Cannot post sales invoice without line items.');
        }

        $debtorAccount = $this->resolveDebtorAccount($invoice);
        $defaultIncome = $this->resolveDefaultIncomeAccount($invoice, $fallbackIncomeAccountId);

        $netTaxableTotal = round((float) $invoice->total - (float) $invoice->tax_amount, 2);
        $incomeCredits = $this->allocateNetTaxableByAccount(
            $invoice->lines,
            $netTaxableTotal,
            fn (SalesInvoiceLine $line) => $this->resolveSalesIncomeAccountId($line, $invoice, $defaultIncome)
        );

        $taxCredits = $this->aggregateTaxCredits(
            $invoice->lines,
            'sales',
            $invoice->company_id,
            $invoice->financial_year_id,
            $invoice->created_by,
            $invoice->created_by_ip,
            "Tax for Sales Invoice #{$invoice->invoice_number}"
        );

        $lines = [];

        foreach ($incomeCredits as $accountId => $amount) {
            if ($amount <= 0) {
                continue;
            }
            $lines[] = [
                'account_id' => $accountId,
                'debit' => 0,
                'credit' => round($amount, 2),
                'description' => "Sales — Invoice #{$invoice->invoice_number}",
            ];
        }

        foreach ($taxCredits as $row) {
            $lines[] = $row;
        }

        $lines[] = [
            'account_id' => $debtorAccount->id,
            'party_id' => $invoice->party_id,
            'debit' => round((float) $invoice->total, 2),
            'credit' => 0,
            'description' => "Receivable — Invoice #{$invoice->invoice_number}",
        ];

        $this->assertBalanced($lines, (float) $invoice->total);

        return $lines;
    }

    /**
     * @return array<int, array{account_id:int,debit:float,credit:float,description:string}>
     */
    public function buildPurchaseVoucherLines(PurchaseInvoice $invoice, ?int $fallbackExpenseAccountId = null): array
    {
        $invoice->loadMissing(['lines.item', 'lines.taxRate', 'lines.account', 'party']);

        if ($invoice->lines->isEmpty()) {
            throw new \RuntimeException('Cannot post purchase invoice without line items.');
        }

        $creditorAccount = $this->resolveCreditorAccount($invoice);
        $defaultExpense = $this->resolveDefaultExpenseAccount($invoice, $fallbackExpenseAccountId);

        $netTaxableTotal = round((float) $invoice->total - (float) $invoice->tax_amount, 2);
        $expenseDebits = $this->allocateNetTaxableByAccount(
            $invoice->lines,
            $netTaxableTotal,
            fn (PurchaseInvoiceLine $line) => $this->resolvePurchaseExpenseAccountId($line, $invoice, $defaultExpense)
        );

        $taxDebits = $this->aggregateTaxDebits(
            $invoice->lines,
            'purchase',
            $invoice->company_id,
            $invoice->financial_year_id,
            $invoice->created_by,
            $invoice->created_by_ip,
            "Tax for Purchase Invoice #{$invoice->invoice_number}"
        );

        $lines = [];

        foreach ($expenseDebits as $accountId => $amount) {
            if ($amount <= 0) {
                continue;
            }
            $lines[] = [
                'account_id' => $accountId,
                'debit' => round($amount, 2),
                'credit' => 0,
                'description' => "Purchase — Invoice #{$invoice->invoice_number}",
            ];
        }

        foreach ($taxDebits as $row) {
            $lines[] = $row;
        }

        $lines[] = [
            'account_id' => $creditorAccount->id,
            'party_id' => $invoice->party_id,
            'debit' => 0,
            'credit' => round((float) $invoice->total, 2),
            'description' => "Payable — Invoice #{$invoice->invoice_number}",
        ];

        $this->assertBalanced($lines, (float) $invoice->total);

        return $lines;
    }

    /**
     * @param iterable<SalesInvoiceLine|PurchaseInvoiceLine> $lines
     * @param callable $accountResolver fn($line): int
     * @return array<int, float> account_id => amount
     */
    protected function allocateNetTaxableByAccount(iterable $lines, float $netTaxableTotal, callable $accountResolver): array
    {
        $lineCollection = collect($lines);
        if ($lineCollection->isEmpty()) {
            return [];
        }

        $rawWeights = [];
        foreach ($lineCollection as $line) {
            $rawWeights[] = max(0, round((float) $line->total - (float) $line->tax_amount, 2));
        }

        $rawSum = array_sum($rawWeights);
        $allocated = $this->distributeAmount($netTaxableTotal, $rawWeights);

        $byAccount = [];
        foreach ($lineCollection->values() as $index => $line) {
            $accountId = $accountResolver($line);
            $byAccount[$accountId] = ($byAccount[$accountId] ?? 0) + $allocated[$index];
        }

        return $byAccount;
    }

    /**
     * Split a total across weights; last bucket absorbs rounding remainder.
     *
     * @param array<int, float> $weights
     * @return array<int, float>
     */
    protected function distributeAmount(float $total, array $weights): array
    {
        $total = round($total, 2);
        if ($total <= 0) {
            return array_fill(0, count($weights), 0.0);
        }

        $weightSum = array_sum($weights);
        if ($weightSum <= 0) {
            $even = round($total / max(1, count($weights)), 2);
            $parts = array_fill(0, count($weights), $even);
            $parts[count($parts) - 1] += round($total - array_sum($parts), 2);

            return $parts;
        }

        $parts = [];
        $running = 0.0;
        $lastIndex = count($weights) - 1;

        foreach ($weights as $index => $weight) {
            if ($index === $lastIndex) {
                $parts[] = round($total - $running, 2);
                continue;
            }

            $share = round($total * ($weight / $weightSum), 2);
            $parts[] = $share;
            $running += $share;
        }

        return $parts;
    }

    /**
     * @param iterable<SalesInvoiceLine|PurchaseInvoiceLine> $lines
     * @return array<int, array{account_id:int,debit:float,credit:float,description:string}>
     */
    protected function aggregateTaxCredits(
        iterable $lines,
        string $context,
        int $companyId,
        ?int $financialYearId,
        ?int $createdBy,
        ?string $createdByIp,
        string $description
    ): array {
        $buckets = [];

        foreach ($lines as $line) {
            if (!$line->taxRate || (float) $line->tax_amount === 0.0) {
                continue;
            }

            $ledgerId = $this->resolveTaxLedgerId(
                $line->taxRate,
                $companyId,
                $context,
                $financialYearId,
                $createdBy,
                $createdByIp
            );

            $buckets[$ledgerId] = ($buckets[$ledgerId] ?? 0) + (float) $line->tax_amount;
        }

        $rows = [];
        foreach ($buckets as $ledgerId => $taxAmount) {
            $taxAmount = round($taxAmount, 2);
            if ($taxAmount === 0.0) {
                continue;
            }

            $rows[] = [
                'account_id' => $ledgerId,
                'debit' => $taxAmount < 0 ? abs($taxAmount) : 0,
                'credit' => $taxAmount > 0 ? $taxAmount : 0,
                'description' => $description,
            ];
        }

        return $rows;
    }

    /**
     * @param iterable<SalesInvoiceLine|PurchaseInvoiceLine> $lines
     * @return array<int, array{account_id:int,debit:float,credit:float,description:string}>
     */
    protected function aggregateTaxDebits(
        iterable $lines,
        string $context,
        int $companyId,
        ?int $financialYearId,
        ?int $createdBy,
        ?string $createdByIp,
        string $description
    ): array {
        $buckets = [];

        foreach ($lines as $line) {
            if (!$line->taxRate || (float) $line->tax_amount === 0.0) {
                continue;
            }

            $ledgerId = $this->resolveTaxLedgerId(
                $line->taxRate,
                $companyId,
                $context,
                $financialYearId,
                $createdBy,
                $createdByIp
            );

            $buckets[$ledgerId] = ($buckets[$ledgerId] ?? 0) + (float) $line->tax_amount;
        }

        $rows = [];
        foreach ($buckets as $ledgerId => $taxAmount) {
            $taxAmount = round($taxAmount, 2);
            if ($taxAmount === 0.0) {
                continue;
            }

            $rows[] = [
                'account_id' => $ledgerId,
                'debit' => $taxAmount > 0 ? $taxAmount : 0,
                'credit' => $taxAmount < 0 ? abs($taxAmount) : 0,
                'description' => $description,
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, array{account_id:int,debit:float,credit:float,description:string}> $lines
     */
    public function assertBalanced(array $lines, float $expectedTotal, float $tolerance = 0.01): void
    {
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $totalDebit += (float) ($line['debit'] ?? 0);
            $totalCredit += (float) ($line['credit'] ?? 0);
        }

        $totalDebit = round($totalDebit, 2);
        $totalCredit = round($totalCredit, 2);

        if (abs($totalDebit - $totalCredit) > $tolerance) {
            throw new \RuntimeException(
                "Accounting entry is not balanced. Debit {$totalDebit} ≠ Credit {$totalCredit} (difference "
                . round($totalDebit - $totalCredit, 2) . ').'
            );
        }

        if ($expectedTotal > 0 && abs($totalDebit - $expectedTotal) > $tolerance) {
            throw new \RuntimeException(
                "Voucher total {$totalDebit} does not match invoice total {$expectedTotal}."
            );
        }
    }

    protected function resolveSalesIncomeAccountId(
        SalesInvoiceLine $line,
        SalesInvoice $invoice,
        Account $defaultIncome
    ): int {
        if ($line->account_id) {
            $account = Account::where('company_id', $invoice->company_id)
                ->where('id', $line->account_id)
                ->where('account_type', 'income')
                ->first();
            if ($account) {
                return (int) $account->id;
            }
        }

        if ($line->item_id && $line->relationLoaded('item') && $line->item?->income_account_id) {
            return (int) $line->item->income_account_id;
        }

        if ($line->item_id) {
            $line->loadMissing('item');
            if ($line->item?->income_account_id) {
                return (int) $line->item->income_account_id;
            }
        }

        return (int) $defaultIncome->id;
    }

    protected function resolvePurchaseExpenseAccountId(
        PurchaseInvoiceLine $line,
        PurchaseInvoice $invoice,
        Account $defaultExpense
    ): int {
        if ($line->account_id) {
            $account = Account::where('company_id', $invoice->company_id)
                ->where('id', $line->account_id)
                ->whereIn('account_type', ['expense', 'asset'])
                ->first();
            if ($account) {
                return (int) $account->id;
            }
        }

        if ($line->item_id) {
            $line->loadMissing('item');
            if ($line->item?->expense_account_id) {
                return (int) $line->item->expense_account_id;
            }
        }

        return (int) $defaultExpense->id;
    }

    protected function resolveDebtorAccount(SalesInvoice $invoice): Account
    {
        return $this->resolveSystemAccount(
            Account::CODE_AR,
            $invoice->company_id,
            $invoice->financial_year_id,
            'Accounts Receivable',
            'asset',
            'debit',
            $invoice->created_by,
            $invoice->created_by_ip,
            'System AR account for sales posting.'
        );
    }

    protected function resolveCreditorAccount(PurchaseInvoice $invoice): Account
    {
        return $this->resolveSystemAccount(
            Account::CODE_AP,
            $invoice->company_id,
            $invoice->financial_year_id,
            'Accounts Payable',
            'liability',
            'credit',
            $invoice->created_by,
            $invoice->created_by_ip,
            'System AP account for purchase posting.'
        );
    }

    protected function resolveDefaultIncomeAccount(SalesInvoice $invoice, ?int $accountId = null): Account
    {
        if ($accountId) {
            $explicit = Account::where('company_id', $invoice->company_id)
                ->where('account_type', 'income')
                ->where('id', $accountId)
                ->first();
            if ($explicit) {
                return $explicit;
            }
        }

        $byCode = $this->accountRepository->findByCode(
            Account::CODE_AR_INCOME,
            $invoice->company_id,
            $invoice->financial_year_id
        );

        if ($byCode) {
            return $byCode;
        }

        $fallback = $this->accountRepository->getByType('income', $invoice->company_id)
            ->firstWhere('is_system', true)
            ?? $this->accountRepository->getByType('income', $invoice->company_id)->first();

        if ($fallback) {
            return $fallback;
        }

        return $this->resolveSystemAccount(
            Account::CODE_AR_INCOME,
            $invoice->company_id,
            $invoice->financial_year_id,
            'Sales Revenue',
            'income',
            'credit',
            $invoice->created_by,
            $invoice->created_by_ip,
            'System income account for sales posting.'
        );
    }

    protected function resolveDefaultExpenseAccount(PurchaseInvoice $invoice, ?int $accountId = null): Account
    {
        if ($accountId) {
            $explicit = Account::where('company_id', $invoice->company_id)
                ->where('account_type', 'expense')
                ->where('id', $accountId)
                ->first();
            if ($explicit) {
                return $explicit;
            }
        }

        $byCode = $this->accountRepository->findByCode(
            Account::CODE_AP_EXPENSE,
            $invoice->company_id,
            $invoice->financial_year_id
        );

        if ($byCode) {
            return $byCode;
        }

        $fallback = Account::where('company_id', $invoice->company_id)
            ->where('account_type', 'expense')
            ->where('is_system', true)
            ->first()
            ?? Account::where('company_id', $invoice->company_id)
                ->where('account_type', 'expense')
                ->first();

        if ($fallback) {
            return $fallback;
        }

        return $this->resolveSystemAccount(
            Account::CODE_AP_EXPENSE,
            $invoice->company_id,
            $invoice->financial_year_id,
            'Purchase / Expense',
            'expense',
            'debit',
            $invoice->created_by,
            $invoice->created_by_ip,
            'System expense account for purchase posting.'
        );
    }

    protected function resolveTaxLedgerId(
        TaxRate $taxRate,
        int $companyId,
        string $context,
        ?int $financialYearId = null,
        ?int $createdBy = null,
        ?string $createdByIp = null
    ): int {
        $key = match ($taxRate->tax_category) {
            'GST', 'CGST', 'SGST', 'IGST' => $context === 'sales' ? 'sales_tax_ledger_id' : 'purchase_tax_ledger_id',
            'TDS' => 'tds_ledger_id',
            'TCS' => 'tcs_ledger_id',
            'CESS' => 'cess_ledger_id',
            default => $context === 'sales' ? 'sales_tax_ledger_id' : 'purchase_tax_ledger_id',
        };

        $ledgerId = $this->settingsService->get($key, null, $companyId);

        if ($ledgerId) {
            $account = Account::where('company_id', $companyId)->where('id', (int) $ledgerId)->first();
            if ($account) {
                return (int) $ledgerId;
            }
        }

        $defaultTaxAccount = $this->ensureTaxPostingAccount(
            $companyId,
            $financialYearId,
            $createdBy,
            $createdByIp,
            $context
        );

        Setting::setValue($key, (string) $defaultTaxAccount->id, $companyId, 'accounting');

        return (int) $defaultTaxAccount->id;
    }

    protected function ensureTaxPostingAccount(
        int $companyId,
        ?int $financialYearId,
        ?int $createdBy,
        ?string $createdByIp,
        string $context
    ): Account {
        $defaultName = $context === 'sales' ? 'Output Tax Payable' : 'Input Tax Credit';

        $existing = Account::withTrashed()
            ->where('company_id', $companyId)
            ->where('account_type', $context === 'sales' ? 'liability' : 'asset')
            ->where('account_name', $defaultName)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->update([
                'is_active' => true,
                'entry_source' => 'system',
                'updated_by' => $createdBy,
                'updated_by_ip' => $createdByIp,
            ]);

            return $existing;
        }

        return Account::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'account_code' => Account::generateCode($context === 'sales' ? 'liability' : 'asset', $companyId),
            'account_name' => $defaultName,
            'account_type' => $context === 'sales' ? 'liability' : 'asset',
            'entry_source' => 'system',
            'opening_balance' => 0,
            'balance_type' => $context === 'sales' ? 'credit' : 'debit',
            'opening_date' => now()->toDateString(),
            'remarks' => 'System tax posting account.',
            'is_active' => true,
            'is_system' => false,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'created_by_ip' => $createdByIp,
            'updated_by_ip' => $createdByIp,
        ]);
    }

    protected function resolveSystemAccount(
        string $code,
        int $companyId,
        ?int $financialYearId,
        string $name,
        string $type,
        string $balanceType,
        ?int $createdBy,
        ?string $createdByIp,
        string $remarks
    ): Account {
        $existing = Account::withTrashed()
            ->where('company_id', $companyId)
            ->where('account_code', $code)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->update([
                'financial_year_id' => $existing->financial_year_id ?: $financialYearId,
                'account_name' => $name,
                'account_type' => $type,
                'entry_source' => 'system',
                'balance_type' => $balanceType,
                'remarks' => $remarks,
                'is_active' => true,
                'updated_by' => $createdBy,
                'updated_by_ip' => $createdByIp,
            ]);

            return $existing;
        }

        return Account::create([
            'uuid' => (string) Str::uuid(),
            'company_id' => $companyId,
            'financial_year_id' => $financialYearId,
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'entry_source' => 'system',
            'opening_balance' => 0,
            'balance_type' => $balanceType,
            'opening_date' => now()->toDateString(),
            'remarks' => $remarks,
            'is_active' => true,
            'is_system' => true,
            'created_by' => $createdBy,
            'updated_by' => $createdBy,
            'created_by_ip' => $createdByIp,
            'updated_by_ip' => $createdByIp,
        ]);
    }
}
