<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Voucher;
use App\Models\VoucherLine;
use App\Models\Account;
use App\Models\Setting;
use App\Models\TaxRate;
use App\Interfaces\SalesInvoiceRepositoryInterface;
use App\Interfaces\AccountRepositoryInterface;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesInvoiceService
{
    protected VoucherService $voucherService;
    protected SalesInvoiceRepositoryInterface $salesInvoiceRepository;
    protected AccountRepositoryInterface $accountRepository;
    protected SettingsService $settingsService;

    public function __construct(
        VoucherService $voucherService,
        SalesInvoiceRepositoryInterface $salesInvoiceRepository,
        AccountRepositoryInterface $accountRepository,
        SettingsService $settingsService
    ) {
        $this->voucherService = $voucherService;
        $this->salesInvoiceRepository = $salesInvoiceRepository;
        $this->accountRepository = $accountRepository;
        $this->settingsService = $settingsService;
    }

    /**
     * Get all sales invoices for a company.
     */
    public function getAll(int $companyId, array $filters = []): Collection
    {
        $query = SalesInvoice::with(['party', 'financialYear'])
            ->where('company_id', $companyId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['party_id'])) {
            $query->where('party_id', $filters['party_id']);
        }
        if (isset($filters['date_from'])) {
            $query->where('invoice_date', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('invoice_date', '<=', $filters['date_to']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('invoice_number', 'like', "%{$filters['search']}%")
                  ->orWhereHas('party', function ($pq) use ($filters) {
                      $pq->where('name', 'like', "%{$filters['search']}%");
                  });
            });
        }

        return $query->orderBy('invoice_date', 'desc')->get();
    }

    /**
     * Get paginated sales invoices.
     */
    public function getPaginated(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SalesInvoice::with(['party'])
            ->where('company_id', $companyId);

        if (isset($filters['invoice_type'])) {
            $query->where('invoice_type', $filters['invoice_type']);
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('invoice_number', 'like', "%{$filters['search']}%")
                  ->orWhereHas('party', function ($pq) use ($filters) {
                      $pq->where('name', 'like', "%{$filters['search']}%");
                  });
            });
        }

        return $query->orderBy('invoice_date', 'desc')->paginate($perPage);
    }

    /**
     * Get invoice by ID with lines.
     */
    public function getById(int $id): ?SalesInvoice
    {
        return $this->salesInvoiceRepository->find($id, ['*'], ['party', 'financialYear', 'lines.item', 'lines.taxRate', 'lines.account']);
    }

    /**
     * Create a sales invoice with lines.
     */
    public function create(array $data, array $lines, array $serviceLines = []): SalesInvoice
    {
        return DB::transaction(function () use ($data, $lines, $serviceLines) {
            $companyId = $data['company_id'] ?? null;
            
            if (!$companyId) {
                throw new \Exception('Company ID is required');
            }

            $invoice = $this->salesInvoiceRepository->create($data);

            foreach ($lines as $index => $line) {
                // Validate that tax_rate_id belongs to the correct company
                if (isset($line['tax_rate_id'])) {
                    $taxRate = TaxRate::where('id', $line['tax_rate_id'])
                        ->where('company_id', $companyId)
                        ->first();
                    
                    if (!$taxRate && $line['tax_rate_id'] !== null) {
                        throw new \Exception('Invalid tax rate for this company');
                    }
                } else {
                    $taxRate = null;
                }

                $line['sales_invoice_id'] = $invoice->id;
                $line['sort_order'] = $index;
                $line['line_type'] = 'item';

                // Calculate line total
                $base = ($line['quantity'] ?? 1) * ($line['unit_price'] ?? 0);
                $discount = $base * (($line['discount_percentage'] ?? 0) / 100);
                $afterDiscount = $base - $discount;

                if (!isset($line['tax_amount']) && $taxRate) {
                    $line['tax_amount'] = $taxRate->calculateTax((float) $afterDiscount);
                }

                $line['total'] = $afterDiscount + ($line['tax_amount'] ?? 0);
                SalesInvoiceLine::create($line);
            }

            // Service lines
            foreach ($serviceLines as $index => $sLine) {
                $taxRate = null;
                if (!empty($sLine['tax_rate_id'])) {
                    $taxRate = TaxRate::where('id', $sLine['tax_rate_id'])
                        ->where('company_id', $companyId)
                        ->first();
                }
                $amount = (float) ($sLine['amount'] ?? 0);
                $tax = $taxRate ? $taxRate->calculateTax($amount) : 0;

                SalesInvoiceLine::create([
                    'sales_invoice_id'    => $invoice->id,
                    'line_type'           => 'service',
                    'account_id'          => $sLine['account_id'] ?? null,
                    'tax_rate_id'         => $sLine['tax_rate_id'] ?? null,
                    'description'         => $sLine['description'] ?? null,
                    'quantity'            => 1,
                    'unit_price'          => $amount,
                    'discount_percentage' => 0,
                    'discount_amount'     => 0,
                    'tax_amount'          => $tax,
                    'total'               => $amount + $tax,
                    'sort_order'          => count($lines) + $index,
                ]);
            }

            $invoice->calculateTotals();
            return $invoice->load('lines');
        });
    }

    /**
     * Generate voucher from sales invoice via VoucherService.
     */
    public function generateVoucher(SalesInvoice $invoice, ?int $accountId = null): ?Voucher
    {
        return DB::transaction(function () use ($invoice, $accountId) {
            // Resolve posting heads. Taxable value posts to Sales Revenue head, tax posts to Tax Ledger head.
            $salesAccount = $this->resolveSalesIncomeAccount($invoice, $accountId);
            $debtorAccount = $this->resolveDebtorAccount($invoice);

            if (!$salesAccount || !$debtorAccount) {
                return null;
            }

            $invoice->loadMissing('lines.taxRate');

            $lines = [];
            $baseAmount = 0;
            $taxPostingLines = [];

            foreach ($invoice->lines as $line) {
                $lineBaseAmount = (float) $line->total - (float) $line->tax_amount;
                $baseAmount += $lineBaseAmount;

                if (!$line->taxRate || (float) $line->tax_amount === 0.0) {
                    continue;
                }

                $ledgerId = $this->resolveTaxLedgerId(
                    $line->taxRate,
                    $invoice->company_id,
                    'sales',
                    $invoice->financial_year_id,
                    $invoice->created_by,
                    $invoice->created_by_ip
                );

                if (!$ledgerId) {
                    throw new \Exception("Tax ledger account not configured for tax rate: {$line->taxRate->name}. Please configure tax posting accounts in settings.");
                }

                if (!isset($taxPostingLines[$ledgerId])) {
                    $taxPostingLines[$ledgerId] = 0.0;
                }

                $taxPostingLines[$ledgerId] += (float) $line->tax_amount;
            }

            // Credit sales account for base invoice amount.
            $lines[] = [
                'account_id' => $salesAccount->id,
                'debit' => 0,
                'credit' => round($baseAmount, 2),
                'description' => "Sales from Invoice #{$invoice->invoice_number}",
            ];

            foreach ($taxPostingLines as $ledgerId => $taxAmount) {
                $lines[] = [
                    'account_id' => $ledgerId,
                    'debit' => $taxAmount < 0 ? round(abs($taxAmount), 2) : 0,
                    'credit' => $taxAmount > 0 ? round($taxAmount, 2) : 0,
                    'description' => "Tax for Sales Invoice #{$invoice->invoice_number}",
                ];
            }

            // Debit debtor account for total amount
            $lines[] = [
                'account_id' => $debtorAccount->id,
                'debit' => round((float) $invoice->total, 2),
                'credit' => 0,
                'description' => "Receivable from Invoice #{$invoice->invoice_number}",
            ];

            // Call VoucherService to create voucher and ledger entries
            $voucher = $this->voucherService->createFromSalesInvoice([
                'company_id' => $invoice->company_id,
                'financial_year_id' => $invoice->financial_year_id,
                'party_id' => $invoice->party_id,
                'voucher_date' => $invoice->invoice_date,
                'narration' => "Sales Invoice #{$invoice->invoice_number}",
                'total' => $invoice->total,
                'sales_invoice_id' => $invoice->id,
            ], $lines);

            $invoice->update(['status' => 'sent']);

            return $voucher;
        });
    }

    /**
     * Update a sales invoice.
     */
    public function update(int $id, array $data): bool
    {
        return $this->salesInvoiceRepository->update($id, $data);
    }

    /**
     * Update invoice with lines.
     */
    public function updateWithLines(int $id, array $data, array $lines, array $serviceLines = []): SalesInvoice
    {
        return DB::transaction(function () use ($id, $data, $lines, $serviceLines) {
            $invoice = $this->salesInvoiceRepository->find($id);
            $invoice->update($data);

            // Delete existing lines and recreate
            $invoice->lines()->delete();

            foreach ($lines as $index => $line) {
                $line['sales_invoice_id'] = $invoice->id;
                $line['sort_order'] = $index;
                $line['line_type'] = 'item';

                $base = ($line['quantity'] ?? 1) * ($line['unit_price'] ?? 0);
                $discount = $base * (($line['discount_percentage'] ?? 0) / 100);
                $afterDiscount = $base - $discount;

                if (!isset($line['tax_amount']) && isset($line['tax_rate_id'])) {
                    $taxRate = TaxRate::find($line['tax_rate_id']);
                    $line['tax_amount'] = $taxRate ? $taxRate->calculateTax((float) $afterDiscount) : 0;
                }

                $line['total'] = $afterDiscount + ($line['tax_amount'] ?? 0);
                SalesInvoiceLine::create($line);
            }

            // Service lines
            foreach ($serviceLines as $index => $sLine) {
                $taxRate = null;
                if (!empty($sLine['tax_rate_id'])) {
                    $taxRate = TaxRate::find($sLine['tax_rate_id']);
                }
                $amount = (float) ($sLine['amount'] ?? 0);
                $tax = $taxRate ? $taxRate->calculateTax($amount) : 0;

                SalesInvoiceLine::create([
                    'sales_invoice_id'    => $invoice->id,
                    'line_type'           => 'service',
                    'account_id'          => $sLine['account_id'] ?? null,
                    'tax_rate_id'         => $sLine['tax_rate_id'] ?? null,
                    'description'         => $sLine['description'] ?? null,
                    'quantity'            => 1,
                    'unit_price'          => $amount,
                    'discount_percentage' => 0,
                    'discount_amount'     => 0,
                    'tax_amount'          => $tax,
                    'total'               => $amount + $tax,
                    'sort_order'          => count($lines) + $index,
                ]);
            }

            $invoice->calculateTotals();
            return $invoice->load('lines');
        });
    }

    /**
     * Record a payment against an invoice.
     */
    public function recordPayment(int $invoiceId, float $amount): SalesInvoice
    {
        $invoice = $this->salesInvoiceRepository->find($invoiceId);
        $invoice->recordPayment($amount);
        return $invoice;
    }

    /**
     * Delete a sales invoice.
     */
    public function delete(int $id): bool
    {
        $invoice = $this->salesInvoiceRepository->find($id);
        if (!$invoice) {
            return false;
        }
        if (in_array($invoice->status, ['paid', 'partial'])) {
            return false;
        }
        return $this->salesInvoiceRepository->delete($id);
    }

    /**
     * Get overdue invoices.
     */
    public function getOverdue(int $companyId): Collection
    {
        return $this->salesInvoiceRepository->getOverdueByCompany($companyId);
    }

    /**
     * Generate next invoice number.
     */
    public function generateInvoiceNumber(int $companyId, int $financialYearId, string $invoiceType = 'item'): string
    {
        $prefix = $invoiceType === 'service' ? 'SRV' : 'INV';

        $lastInvoice = SalesInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('invoice_type', $invoiceType)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice
            ? intval(substr($lastInvoice->invoice_number, -6)) + 1
            : 1;

        return $prefix . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    protected function resolveTaxLedgerId(
        TaxRate $taxRate,
        int $companyId,
        string $context,
        ?int $financialYearId = null,
        ?int $createdBy = null,
        ?string $createdByIp = null
    ): ?int
    {
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

    protected function resolveSalesIncomeAccount(SalesInvoice $invoice, ?int $accountId = null): ?Account
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

        return $this->upsertSystemAccountByCode(
            Account::CODE_AR_INCOME,
            $invoice->company_id,
            $invoice->financial_year_id,
            'Sales Revenue (AR)',
            'income',
            'credit',
            $invoice->created_by,
            $invoice->created_by_ip,
            'System income account for taxable sales posting.'
        );
    }

    protected function resolveDebtorAccount(SalesInvoice $invoice): ?Account
    {
        $byCode = $this->accountRepository->findByCode(
            Account::CODE_AR,
            $invoice->company_id,
            $invoice->financial_year_id
        );

        if ($byCode) {
            return $byCode;
        }

        $assetFallback = $this->accountRepository->getByType('asset', $invoice->company_id)
            ->firstWhere('is_system', true)
            ?? $this->accountRepository->getByType('asset', $invoice->company_id)->first();

        if ($assetFallback) {
            return $assetFallback;
        }

        return $this->upsertSystemAccountByCode(
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
            ->where('account_type', 'liability')
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
            'account_code' => Account::generateCode('liability', $companyId),
            'account_name' => $defaultName,
            'account_type' => 'liability',
            'entry_source' => 'system',
            'opening_balance' => 0,
            'balance_type' => 'credit',
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

    protected function upsertSystemAccountByCode(
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
