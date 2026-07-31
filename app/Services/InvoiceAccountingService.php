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
        $defaultServiceIncome = $this->resolveDefaultServiceIncomeAccount($invoice);

        $netTaxableTotal = round((float) $invoice->total - (float) $invoice->tax_amount, 2);
        $incomeCredits = $this->allocateNetTaxableByAccount(
            $invoice->lines,
            $netTaxableTotal,
            fn (SalesInvoiceLine $line) => $this->resolveSalesIncomeAccountId(
                $line,
                $invoice,
                $defaultIncome,
                $defaultServiceIncome
            )
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
        Account $defaultIncome,
        Account $defaultServiceIncome
    ): int {
        $line->loadMissing('item');
        $isService = $line->line_type === 'service' || $line->item?->type === 'service';

        if ($line->account_id) {
            $account = Account::where('company_id', $invoice->company_id)
                ->where('id', $line->account_id)
                ->where('account_type', 'income')
                ->first();
            if ($account) {
                if ($isService && $account->account_code === Account::CODE_AR_INCOME) {
                    return (int) $defaultServiceIncome->id;
                }

                return (int) $account->id;
            }
        }

        if ($line->item?->income_account_id) {
            if ($isService && (int) $line->item->income_account_id === (int) $defaultIncome->id) {
                return (int) $defaultServiceIncome->id;
            }

            return (int) $line->item->income_account_id;
        }

        return (int) ($isService ? $defaultServiceIncome->id : $defaultIncome->id);
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
        $invoice->loadMissing(['account', 'party.account']);

        if (
            $invoice->account
            && (int) $invoice->account->company_id === (int) $invoice->company_id
            && (bool) $invoice->account->is_active
        ) {
            return $invoice->account;
        }

        $partyAccount = $invoice->party?->account;

        if (
            $partyAccount
            && (int) $partyAccount->company_id === (int) $invoice->company_id
            && (bool) $partyAccount->is_active
            && (bool) $partyAccount->is_cash_bank_od
        ) {
            return $partyAccount;
        }

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
        $invoice->loadMissing(['account', 'party.account']);

        if (
            $invoice->account
            && (int) $invoice->account->company_id === (int) $invoice->company_id
            && (bool) $invoice->account->is_active
        ) {
            return $invoice->account;
        }

        $partyAccount = $invoice->party?->account;

        if (
            $partyAccount
            && (int) $partyAccount->company_id === (int) $invoice->company_id
            && (bool) $partyAccount->is_active
            && (bool) $partyAccount->is_cash_bank_od
        ) {
            return $partyAccount;
        }

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

    protected function resolveDefaultServiceIncomeAccount(SalesInvoice $invoice): Account
    {
        $account = $this->accountRepository->findByCode(
            Account::CODE_SERVICE_INCOME,
            $invoice->company_id,
            $invoice->financial_year_id
        );

        if ($account) {
            return $account;
        }

        return $this->resolveSystemAccount(
            Account::CODE_SERVICE_INCOME,
            $invoice->company_id,
            $invoice->financial_year_id,
            'Service Revenue',
            'income',
            'credit',
            $invoice->created_by,
            $invoice->created_by_ip,
            'System income account for service taxable totals on sales invoices.'
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

        // Sales / Purchase invoice tax always posts to reserved system ledgers.
        if (in_array($key, ['sales_tax_ledger_id', 'purchase_tax_ledger_id'], true)) {
            $taxContext = $key === 'sales_tax_ledger_id' ? 'sales' : 'purchase';
            $defaultTaxAccount = $this->ensureTaxPostingAccount(
                $companyId,
                $financialYearId,
                $createdBy,
                $createdByIp,
                $taxContext
            );

            Setting::setValue($key, (string) $defaultTaxAccount->id, $companyId, 'accounting');

            return (int) $defaultTaxAccount->id;
        }

        $ledgerId = $this->settingsService->get($key, null, $companyId);

        if ($ledgerId) {
            $account = Account::where('company_id', $companyId)
                ->where('id', (int) $ledgerId)
                ->where('account_code', '!=', Account::CODE_SUSPENSE)
                ->first();
            if ($account) {
                return (int) $account->id;
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
        $code = $context === 'sales' ? Account::CODE_SALES_TAX : Account::CODE_PURCHASE_TAX;
        $name = $context === 'sales' ? 'Sales Tax' : 'Purchase Tax';
        $type = $context === 'sales' ? 'liability' : 'asset';
        $balanceType = $context === 'sales' ? 'credit' : 'debit';

        $existing = Account::withTrashed()
            ->where('company_id', $companyId)
            ->where('account_code', $code)
            ->first();

        if ($existing) {
            if ($existing->trashed()) {
                $existing->restore();
            }

            $existing->update([
                'account_name' => $name,
                'account_type' => $type,
                'balance_type' => $balanceType,
                'is_active' => true,
                'is_system' => true,
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
            'account_code' => $code,
            'account_name' => $name,
            'account_type' => $type,
            'entry_source' => 'system',
            'opening_balance' => 0,
            'balance_type' => $balanceType,
            'opening_date' => now()->toDateString(),
            'remarks' => $context === 'sales'
                ? 'Default ledger for tax amount from sales invoice lines.'
                : 'Default ledger for tax amount from purchase invoice lines.',
            'is_active' => true,
            'is_system' => true,
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
