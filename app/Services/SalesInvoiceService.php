<?php

namespace App\Services;

use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use App\Models\Item;
use App\Models\Voucher;
use App\Models\TaxRate;
use App\Models\FinancialYear;
use App\Interfaces\SalesInvoiceRepositoryInterface;
use App\Interfaces\AccountRepositoryInterface;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SalesInvoiceService
{
    protected VoucherService $voucherService;
    protected SalesInvoiceRepositoryInterface $salesInvoiceRepository;
    protected AccountRepositoryInterface $accountRepository;
    protected SettingsService $settingsService;
    protected InvoiceAccountingService $invoiceAccountingService;
    protected PeriodLockService $periodLockService;
    protected ItemService $itemService;

    public function __construct(
        VoucherService $voucherService,
        SalesInvoiceRepositoryInterface $salesInvoiceRepository,
        AccountRepositoryInterface $accountRepository,
        SettingsService $settingsService,
        InvoiceAccountingService $invoiceAccountingService,
        PeriodLockService $periodLockService,
        ItemService $itemService
    ) {
        $this->voucherService = $voucherService;
        $this->salesInvoiceRepository = $salesInvoiceRepository;
        $this->accountRepository = $accountRepository;
        $this->settingsService = $settingsService;
        $this->invoiceAccountingService = $invoiceAccountingService;
        $this->periodLockService = $periodLockService;
        $this->itemService = $itemService;
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

        return $query->orderBy('invoice_date', 'desc')->orderBy('id', 'desc')->get();
    }

    /**
     * Get paginated sales invoices.
     */
    public function getPaginated(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SalesInvoice::with(['party'])
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

        return $query->orderBy('invoice_date', 'desc')->orderBy('id', 'desc')->paginate($perPage);
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

            $this->periodLockService->assertWritable(
                (int) $companyId,
                $data['invoice_date'],
                isset($data['financial_year_id']) ? (int) $data['financial_year_id'] : null
            );

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

                $item = !empty($line['item_id']) ? Item::find($line['item_id']) : null;
                $isServiceItem = $item && $item->type === 'service';

                $line['sales_invoice_id'] = $invoice->id;
                $line['sort_order'] = $index;
                $line['line_type'] = $isServiceItem ? 'service' : 'item';
                if ($isServiceItem && empty($line['account_id']) && $item->income_account_id) {
                    $line['account_id'] = $item->income_account_id;
                }

                // Calculate line total
                $base = ($line['quantity'] ?? 1) * ($line['unit_price'] ?? 0);
                $discount = $base * (($line['discount_percentage'] ?? 0) / 100);
                $afterDiscount = $base - $discount;

                if (!isset($line['tax_amount']) && $taxRate) {
                    $line['tax_amount'] = $taxRate->calculateTax((float) $afterDiscount);
                }

                $line['discount_amount'] = $discount;
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

            $this->itemService->applyStockFromLines($lines, 'out');

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
            $existing = Voucher::query()
                ->where('sales_invoice_id', $invoice->id)
                ->where('voucher_type', 'income')
                ->where('status', 'posted')
                ->first();

            if ($existing) {
                return $existing->load(['party', 'lines.account']);
            }

            $lines = $this->invoiceAccountingService->buildSalesVoucherLines($invoice, $accountId);

            $voucher = $this->voucherService->createFromSalesInvoice([
                'company_id' => $invoice->company_id,
                'financial_year_id' => $invoice->financial_year_id,
                'party_id' => $invoice->party_id,
                'voucher_date' => $invoice->invoice_date,
                'narration' => filled($invoice->notes)
                    ? (string) $invoice->notes
                    : "Sales Invoice #{$invoice->invoice_number}",
                'total' => $invoice->total,
                'sales_invoice_id' => $invoice->id,
                'created_by' => $invoice->created_by,
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

            if (!$invoice) {
                throw new \RuntimeException('Sales invoice not found.');
            }

            if (in_array($invoice->status, ['paid', 'partial', 'cancelled'], true)) {
                throw new \RuntimeException('Paid, partially paid, or cancelled invoices cannot be altered.');
            }

            $invoiceDate = $data['invoice_date'] ?? $invoice->invoice_date;
            $this->periodLockService->assertWritable(
                (int) $invoice->company_id,
                $invoiceDate,
                $invoice->financial_year_id ? (int) $invoice->financial_year_id : null
            );

            $invoice->update($data);

            $oldLines = $invoice->lines()->where('line_type', 'item')->get();
            $this->itemService->applyStockFromLines($oldLines, 'in');

            // Delete existing lines and recreate
            $invoice->lines()->delete();

            foreach ($lines as $index => $line) {
                $item = !empty($line['item_id']) ? Item::find($line['item_id']) : null;
                $isServiceItem = $item && $item->type === 'service';

                $line['sales_invoice_id'] = $invoice->id;
                $line['sort_order'] = $index;
                $line['line_type'] = $isServiceItem ? 'service' : 'item';
                if ($isServiceItem && empty($line['account_id']) && $item->income_account_id) {
                    $line['account_id'] = $item->income_account_id;
                }

                $base = ($line['quantity'] ?? 1) * ($line['unit_price'] ?? 0);
                $discount = $base * (($line['discount_percentage'] ?? 0) / 100);
                $afterDiscount = $base - $discount;

                if (!isset($line['tax_amount']) && isset($line['tax_rate_id'])) {
                    $taxRate = TaxRate::find($line['tax_rate_id']);
                    $line['tax_amount'] = $taxRate ? $taxRate->calculateTax((float) $afterDiscount) : 0;
                }

                $line['discount_amount'] = $discount;
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

            $this->itemService->applyStockFromLines($lines, 'out');

            $invoice->calculateTotals();
            $invoice->refresh()->load(['lines.item', 'lines.taxRate', 'lines.account', 'party']);

            $postedVoucher = $invoice->vouchers()->where('status', 'posted')->first();
            if ($postedVoucher) {
                $voucherLines = $this->invoiceAccountingService->buildSalesVoucherLines($invoice);
                $this->voucherService->syncInvoiceVoucher(
                    $postedVoucher,
                    [
                        'party_id' => $invoice->party_id,
                        'voucher_date' => $invoice->invoice_date,
                        'narration' => "Sales Invoice #{$invoice->invoice_number}",
                        'total' => $invoice->total,
                        'updated_by' => $invoice->updated_by,
                        'updated_by_ip' => $invoice->updated_by_ip,
                    ],
                    $voucherLines
                );
            }

            return $invoice;
        });
    }

    /**
     * Record a payment against an invoice and post a Receipt voucher (bill-wise).
     *
     * @param  array{amount:float,cash_bank_account_id:int,payment_mode:string,payment_date?:string}  $paymentData
     */
    public function recordPayment(int $invoiceId, array $paymentData): SalesInvoice
    {
        return DB::transaction(function () use ($invoiceId, $paymentData) {
            $invoice = SalesInvoice::query()
                ->whereKey($invoiceId)
                ->lockForUpdate()
                ->with(['party.account'])
                ->first();

            if (!$invoice) {
                throw new \RuntimeException('Sales invoice not found.');
            }

            if ($invoice->status === 'cancelled') {
                throw new \RuntimeException('Cancelled invoices cannot receive payments.');
            }

            if ((float) $invoice->balance_due <= 0 || $invoice->status === 'paid') {
                throw new \RuntimeException('This invoice is already fully paid.');
            }

            $hasPostedSalesVoucher = Voucher::query()
                ->where('sales_invoice_id', $invoice->id)
                ->where('voucher_type', 'income')
                ->where('status', 'posted')
                ->exists();

            if (!$hasPostedSalesVoucher) {
                throw new \RuntimeException('Post the sales invoice to accounts before recording payment.');
            }

            $amount = round((float) ($paymentData['amount'] ?? 0), 2);
            if ($amount <= 0) {
                throw new \RuntimeException('Payment amount must be greater than zero.');
            }
            if ($amount > round((float) $invoice->balance_due, 2) + 0.009) {
                throw new \RuntimeException('Payment amount cannot exceed balance due.');
            }

            $paymentDate = $paymentData['payment_date'] ?? now()->toDateString();
            $settlementFy = $this->periodLockService->assertWritable(
                (int) $invoice->company_id,
                $paymentDate
            );

            $cashBankAccountId = (int) ($paymentData['cash_bank_account_id'] ?? 0);
            $paymentMode = $paymentData['payment_mode'] ?? null;
            $cashBank = \App\Models\Account::query()
                ->where('company_id', $invoice->company_id)
                ->where('id', $cashBankAccountId)
                ->where('is_active', true)
                ->first();

            if (!$cashBank || !in_array($cashBank->transaction_mode, ['cash', 'bank', 'od'], true)) {
                throw new \RuntimeException('Select a valid Cash / Bank / OD account.');
            }
            if ($paymentMode && $cashBank->transaction_mode !== $paymentMode) {
                throw new \RuntimeException('Selected account must match the payment mode.');
            }

            $partyAccountId = $invoice->party?->account_id
                ?: \App\Models\Account::query()
                ->where('company_id', $invoice->company_id)
                ->where('account_code', \App\Models\Account::CODE_AR)
                ->value('id');
            if (!$partyAccountId) {
                throw new \RuntimeException('Accounts Receivable control account is missing.');
            }

            $this->voucherService->create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'company_id' => $invoice->company_id,
                'financial_year_id' => $settlementFy->id,
                'party_id' => $invoice->party_id,
                'voucher_type' => 'receipt',
                'voucher_date' => $paymentDate,
                'narration' => "Receipt against Sales Invoice #{$invoice->invoice_number}",
                'sales_invoice_id' => $invoice->id,
                'created_by' => $paymentData['created_by'] ?? auth()->id(),
                'created_by_ip' => $paymentData['created_by_ip'] ?? request()->ip(),
                'lines' => [
                    [
                        'account_id' => $cashBank->id,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => "Received for Invoice #{$invoice->invoice_number}",
                    ],
                    [
                        'account_id' => (int) $partyAccountId,
                        'party_id' => $invoice->party_id,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => "Receipt against Invoice #{$invoice->invoice_number}",
                    ],
                ],
            ]);

            $invoice->recordPayment($amount);

            return $invoice->fresh(['party', 'financialYear', 'lines.item', 'lines.taxRate', 'lines.account']);
        });
    }

    /**
     * Cancel a sales invoice: reverse settlements, cancel posting voucher, restore stock.
     */
    public function cancel(int $id): SalesInvoice
    {
        return DB::transaction(function () use ($id) {
            $invoice = SalesInvoice::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                throw new \RuntimeException('Sales invoice not found.');
            }

            if ($invoice->status === 'cancelled') {
                throw new \RuntimeException('This sales invoice is already cancelled.');
            }

            $this->periodLockService->assertWritable(
                (int) $invoice->company_id,
                $invoice->invoice_date,
                $invoice->financial_year_id ? (int) $invoice->financial_year_id : null
            );

            $vouchers = Voucher::query()
                ->where('sales_invoice_id', $invoice->id)
                ->where('status', 'posted')
                ->orderByRaw("CASE WHEN voucher_type = 'receipt' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();

            foreach ($vouchers as $voucher) {
                $this->voucherService->cancel((int) $voucher->id, true);
            }

            $itemLines = $invoice->lines()->where('line_type', 'item')->get();
            $this->itemService->applyStockFromLines($itemLines, 'in');

            $invoice->update([
                'status' => 'cancelled',
                'amount_paid' => 0,
                'balance_due' => 0,
            ]);

            return $invoice->fresh(['party', 'financialYear', 'lines.item', 'lines.taxRate', 'lines.account']);
        });
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
        if ($invoice->vouchers()->exists() || in_array($invoice->status, ['sent', 'paid', 'partial', 'cancelled'], true)) {
            throw new \RuntimeException(
                'A posted sales invoice cannot be deleted because accounting entries exist. Cancel it instead.'
            );
        }

        return DB::transaction(function () use ($invoice, $id) {
            $oldLines = $invoice->lines()->where('line_type', 'item')->get();
            $this->itemService->applyStockFromLines($oldLines, 'in');
            return $this->salesInvoiceRepository->delete($id);
        });
    }

    /**
     * Get overdue invoices.
     */
    public function getOverdue(int $companyId): Collection
    {
        return $this->salesInvoiceRepository->getOverdueByCompany($companyId);
    }

    /**
     * Generate next invoice number: INV-202627/0001
     */
    public function generateInvoiceNumber(int $companyId, int $financialYearId): string
    {
        $fy = FinancialYear::find($financialYearId);
        $fyCode = $fy?->code() ?? now()->format('Y') . now()->copy()->addYear()->format('y');
        $needle = 'INV-' . $fyCode . '/';

        $numbers = SalesInvoice::where('company_id', $companyId)
            ->where('financial_year_id', $financialYearId)
            ->where('invoice_number', 'like', $needle . '%')
            ->pluck('invoice_number');

        $max = 0;
        foreach ($numbers as $number) {
            $pos = strrpos($number, '/');
            $seq = $pos === false ? 0 : (int) substr($number, $pos + 1);
            if ($seq > $max) {
                $max = $seq;
            }
        }

        return $needle . str_pad((string) ($max + 1), 4, '0', STR_PAD_LEFT);
    }
}
