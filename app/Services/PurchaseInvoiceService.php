<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Voucher;
use App\Models\VoucherLine;
use App\Models\Account;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseInvoiceService
{
    protected VoucherService $voucherService;
    protected SettingsService $settingsService;

    public function __construct(VoucherService $voucherService, SettingsService $settingsService)
    {
        $this->voucherService = $voucherService;
        $this->settingsService = $settingsService;
    }

    /**
     * Get all purchase invoices for a company.
     */
    public function getAll(int $companyId, array $filters = []): Collection
    {
        $query = PurchaseInvoice::with(['party', 'financialYear'])
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
                  ->orWhere('supplier_invoice_number', 'like', "%{$filters['search']}%")
                  ->orWhereHas('party', function ($pq) use ($filters) {
                      $pq->where('name', 'like', "%{$filters['search']}%");
                  });
            });
        }

        return $query->orderBy('invoice_date', 'desc')->get();
    }

    /**
     * Get paginated purchase invoices.
     */
    public function getPaginated(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseInvoice::with(['party'])
            ->where('company_id', $companyId);

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
    public function getById(int $id): ?PurchaseInvoice
    {
        return PurchaseInvoice::with(['party', 'financialYear', 'lines.item', 'lines.taxRate', 'lines.account'])
            ->find($id);
    }

    /**
     * Create a purchase invoice with lines.
     */
    public function create(array $data, array $lines, array $serviceLines = []): PurchaseInvoice
    {
        return DB::transaction(function () use ($data, $lines, $serviceLines) {
            $invoice = PurchaseInvoice::create($data);

            foreach ($lines as $index => $line) {
                $line['purchase_invoice_id'] = $invoice->id;
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
                PurchaseInvoiceLine::create($line);
            }

            // Service lines
            foreach ($serviceLines as $index => $sLine) {
                $taxRate = null;
                if (!empty($sLine['tax_rate_id'])) {
                    $taxRate = TaxRate::find($sLine['tax_rate_id']);
                }
                $amount = (float) ($sLine['amount'] ?? 0);
                $tax = $taxRate ? $taxRate->calculateTax($amount) : 0;

                PurchaseInvoiceLine::create([
                    'purchase_invoice_id' => $invoice->id,
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
     * Update a purchase invoice with lines.
     */
    public function updateWithLines(int $id, array $data, array $lines, array $serviceLines = []): PurchaseInvoice
    {
        return DB::transaction(function () use ($id, $data, $lines, $serviceLines) {
            $invoice = PurchaseInvoice::findOrFail($id);
            $invoice->update($data);

            $invoice->lines()->delete();

            foreach ($lines as $index => $line) {
                $line['purchase_invoice_id'] = $invoice->id;
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
                PurchaseInvoiceLine::create($line);
            }

            // Service lines
            foreach ($serviceLines as $index => $sLine) {
                $taxRate = null;
                if (!empty($sLine['tax_rate_id'])) {
                    $taxRate = TaxRate::find($sLine['tax_rate_id']);
                }
                $amount = (float) ($sLine['amount'] ?? 0);
                $tax = $taxRate ? $taxRate->calculateTax($amount) : 0;

                PurchaseInvoiceLine::create([
                    'purchase_invoice_id' => $invoice->id,
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
     * Record payment against purchase invoice.
     */
    public function recordPayment(int $invoiceId, float $amount): PurchaseInvoice
    {
        $invoice = PurchaseInvoice::findOrFail($invoiceId);
        $invoice->recordPayment($amount);
        return $invoice;
    }

    /**
     * Delete a purchase invoice.
     */
    public function delete(int $id): bool
    {
        $invoice = PurchaseInvoice::findOrFail($id);
        if (in_array($invoice->status, ['paid', 'partial'])) {
            return false;
        }
        return $invoice->delete();
    }

    /**
     * Get overdue invoices.
     */
    public function getOverdue(int $companyId): Collection
    {
        return PurchaseInvoice::where('company_id', $companyId)->overdue()->get();
    }

    /**
     * Generate next invoice number.
     */
    public function generateInvoiceNumber(int $companyId, int $financialYearId): string
    {
        $lastInvoice = PurchaseInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->orderBy('id', 'desc')
            ->first();

        $nextNumber = $lastInvoice
            ? intval(substr($lastInvoice->invoice_number, -6)) + 1
            : 1;

        return 'PUR-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate voucher from purchase invoice via VoucherService.
     */
    public function generateVoucher(PurchaseInvoice $invoice, ?int $accountId = null): ?Voucher
    {
        return DB::transaction(function () use ($invoice, $accountId) {
            $expenseAccount = $accountId ?? Account::where('company_id', $invoice->company_id)
                ->where('account_type', 'expense')
                ->where('is_system', true)
                ->first()
                ?? Account::where('company_id', $invoice->company_id)
                ->where('account_type', 'expense')
                ->first();

            $creditorAccount = Account::where('company_id', $invoice->company_id)
                ->where('account_type', 'liability')
                ->where('account_name', 'like', '%payable%')
                ->first()
                ?? Account::where('company_id', $invoice->company_id)
                ->where('account_type', 'liability')
                ->first();

            if (!$expenseAccount || !$creditorAccount) {
                return null;
            }

            $invoice->loadMissing('lines.taxRate');

            $baseAmount = 0;
            $taxPostingLines = [];

            foreach ($invoice->lines as $line) {
                $lineBaseAmount = (float) $line->total - (float) $line->tax_amount;
                $baseAmount += $lineBaseAmount;

                if (!$line->taxRate || (float) $line->tax_amount === 0.0) {
                    continue;
                }

                $ledgerId = $this->resolveTaxLedgerId($line->taxRate, $invoice->company_id, 'purchase');

                if (!$ledgerId) {
                    continue;
                }

                if (!isset($taxPostingLines[$ledgerId])) {
                    $taxPostingLines[$ledgerId] = 0.0;
                }

                $taxPostingLines[$ledgerId] += (float) $line->tax_amount;
            }

            $lines = [
                [
                    'account_id' => $expenseAccount->id,
                    'debit' => round($baseAmount, 2),
                    'credit' => 0,
                    'description' => "Purchase from Invoice #{$invoice->invoice_number}",
                ],
            ];

            foreach ($taxPostingLines as $ledgerId => $taxAmount) {
                $lines[] = [
                    'account_id' => $ledgerId,
                    'debit' => $taxAmount > 0 ? round($taxAmount, 2) : 0,
                    'credit' => $taxAmount < 0 ? round(abs($taxAmount), 2) : 0,
                    'description' => "Tax for Purchase Invoice #{$invoice->invoice_number}",
                ];
            }

            $lines[] = [
                'account_id' => $creditorAccount->id,
                'debit' => 0,
                'credit' => round((float) $invoice->total, 2),
                'description' => "Creditor for Purchase Invoice #{$invoice->invoice_number}",
            ];

            // Call VoucherService to create voucher and ledger entries
            $voucher = $this->voucherService->createFromPurchaseInvoice([
                'company_id' => $invoice->company_id,
                'financial_year_id' => $invoice->financial_year_id,
                'party_id' => $invoice->party_id,
                'voucher_date' => $invoice->invoice_date,
                'narration' => "Purchase Invoice #{$invoice->invoice_number}",
                'total' => $invoice->total,
                'purchase_invoice_id' => $invoice->id,
            ], $lines);

            $invoice->update(['status' => 'posted']);

            return $voucher;
        });
    }

    protected function resolveTaxLedgerId(TaxRate $taxRate, int $companyId, string $context): ?int
    {
        $key = match ($taxRate->tax_category) {
            'GST', 'CGST', 'SGST', 'IGST' => $context === 'sales' ? 'sales_tax_ledger_id' : 'purchase_tax_ledger_id',
            'TDS' => 'tds_ledger_id',
            'TCS' => 'tcs_ledger_id',
            'CESS' => 'cess_ledger_id',
            default => $context === 'sales' ? 'sales_tax_ledger_id' : 'purchase_tax_ledger_id',
        };

        $ledgerId = $this->settingsService->get($key, null, $companyId);

        return $ledgerId ? (int) $ledgerId : null;
    }
}
