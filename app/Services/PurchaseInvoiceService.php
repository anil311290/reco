<?php

namespace App\Services;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceLine;
use App\Models\Voucher;
use App\Models\TaxRate;
use App\Models\FinancialYear;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PurchaseInvoiceService
{
    protected VoucherService $voucherService;
    protected InvoiceAccountingService $invoiceAccountingService;
    protected PeriodLockService $periodLockService;
    protected LedgerService $ledgerService;
    protected ItemService $itemService;
    protected PaymentInvoiceMappingService $paymentMappingService;

    public function __construct(
        VoucherService $voucherService,
        InvoiceAccountingService $invoiceAccountingService,
        PeriodLockService $periodLockService,
        LedgerService $ledgerService,
        ItemService $itemService,
        PaymentInvoiceMappingService $paymentMappingService
    ) {
        $this->voucherService = $voucherService;
        $this->invoiceAccountingService = $invoiceAccountingService;
        $this->periodLockService = $periodLockService;
        $this->ledgerService = $ledgerService;
        $this->itemService = $itemService;
        $this->paymentMappingService = $paymentMappingService;
    }

    /**
     * Get all purchase invoices for a company.
     */
    public function getAll(int $companyId, array $filters = []): Collection
    {
        $query = PurchaseInvoice::with(['party', 'financialYear'])
            ->where('company_id', $companyId);

        if (isset($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $query->whereNotIn('status', ['paid', 'cancelled'])->where('due_date', '<', now());
            } else {
                $query->where('status', $filters['status']);
            }
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

        return $query->orderBy('invoice_date', 'desc')->orderBy('id', 'desc')->get();
    }

    /**
     * Get paginated purchase invoices.
     */
    public function getPaginated(int $companyId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = PurchaseInvoice::with(['party'])
            ->where('company_id', $companyId);

        if (isset($filters['status'])) {
            if ($filters['status'] === 'overdue') {
                $query->whereNotIn('status', ['paid', 'cancelled'])->where('due_date', '<', now());
            } else {
                $query->where('status', $filters['status']);
            }
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
    public function getById(int $id): ?PurchaseInvoice
    {
        return PurchaseInvoice::with(['party', 'financialYear', 'lines.item', 'lines.taxRate', 'lines.account'])
            ->find($id);
    }

    /**
     * Create a purchase invoice with lines.
     */
    public function create(array $data, array $lines): PurchaseInvoice
    {
        return DB::transaction(function () use ($data, $lines) {
            if (empty($data['invoice_number'])) {
                $financialYearId = (int) ($data['financial_year_id'] ?? 0);
                if ($financialYearId <= 0) {
                    throw new \Exception('Financial year is required to generate the invoice number.');
                }

                FinancialYear::whereKey($financialYearId)->lockForUpdate()->firstOrFail();
                $data['invoice_number'] = $this->generateInvoiceNumber(
                    (int) $data['company_id'],
                    $financialYearId
                );
            }

            $this->periodLockService->assertWritable(
                (int) $data['company_id'],
                $data['invoice_date'],
                isset($data['financial_year_id']) ? (int) $data['financial_year_id'] : null
            );

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

                $line['discount_amount'] = $discount;
                $line['total'] = $afterDiscount + ($line['tax_amount'] ?? 0);
                PurchaseInvoiceLine::create($line);
            }

            $this->itemService->applyStockFromLines($lines, 'in');

            $invoice->calculateTotals();
            return $invoice->load('lines');
        });
    }

    /**
     * Update a purchase invoice with lines.
     */
    public function updateWithLines(int $id, array $data, array $lines): PurchaseInvoice
    {
        return DB::transaction(function () use ($id, $data, $lines) {
            $invoice = PurchaseInvoice::findOrFail($id);

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
            $this->itemService->applyStockFromLines($oldLines, 'out');

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

                $line['discount_amount'] = $discount;
                $line['total'] = $afterDiscount + ($line['tax_amount'] ?? 0);
                PurchaseInvoiceLine::create($line);
            }

            $this->itemService->applyStockFromLines($lines, 'in');

            $invoice->calculateTotals();
            $invoice->refresh()->load(['lines.item', 'lines.taxRate', 'lines.account', 'party']);

            $postedVoucher = $invoice->vouchers()->where('status', 'posted')->first();
            if ($postedVoucher) {
                $voucherLines = $this->invoiceAccountingService->buildPurchaseVoucherLines($invoice);
                $this->voucherService->syncInvoiceVoucher(
                    $postedVoucher,
                    [
                        'party_id' => $invoice->party_id,
                        'voucher_date' => $invoice->invoice_date,
                        'narration' => "Purchase Invoice #{$invoice->invoice_number}",
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
     * Record payment against purchase invoice and post a Payment voucher (bill-wise).
     *
        * @param  array{amount:float,cash_bank_account_id:int,payment_date?:string}  $paymentData
     */
    public function recordPayment(int $invoiceId, array $paymentData): PurchaseInvoice
    {
        return DB::transaction(function () use ($invoiceId, $paymentData) {
            $invoice = PurchaseInvoice::query()
                ->whereKey($invoiceId)
                ->lockForUpdate()
                ->with(['party.account'])
                ->firstOrFail();

            if ($invoice->status === 'cancelled') {
                throw new \RuntimeException('Cancelled invoices cannot receive payments.');
            }

            if ((float) $invoice->balance_due <= 0 || $invoice->status === 'paid') {
                throw new \RuntimeException('This invoice is already fully paid.');
            }

            $hasPostedPurchaseVoucher = Voucher::query()
                ->where('purchase_invoice_id', $invoice->id)
                ->where('voucher_type', 'expense')
                ->where('status', 'posted')
                ->exists();

            if (!$hasPostedPurchaseVoucher) {
                throw new \RuntimeException('Post the purchase invoice to accounts before recording payment.');
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
            $cashBank = \App\Models\Account::query()
                ->where('company_id', $invoice->company_id)
                ->where('id', $cashBankAccountId)
                ->where('is_active', true)
                ->first();

            if (!$cashBank || !$cashBank->isCashBankOd()) {
                throw new \RuntimeException('Select a valid Cash / Bank / OD account.');
            }
            $available = $this->ledgerService->getAvailablePaymentBalance(
                $cashBank->id,
                (int) $invoice->company_id,
                (int) $settlementFy->id
            );
            if ($available !== null && $amount > $available + 0.009) {
                throw new \RuntimeException(
                    'Insufficient balance in ' . $cashBank->account_name . '. Available: ₹' . number_format($available, 2)
                );
            }

            $partyAccountId = $invoice->party?->account_id
                ?: \App\Models\Account::query()
                ->where('company_id', $invoice->company_id)
                ->where('account_code', \App\Models\Account::CODE_AP)
                ->value('id');
            if (!$partyAccountId) {
                throw new \RuntimeException('Accounts Payable control account is missing.');
            }

            $this->voucherService->create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'company_id' => $invoice->company_id,
                'financial_year_id' => $settlementFy->id,
                'party_id' => $invoice->party_id,
                'voucher_type' => 'payment',
                'voucher_date' => $paymentDate,
                'narration' => "Payment against Purchase Invoice #{$invoice->invoice_number}",
                'purchase_invoice_id' => $invoice->id,
                'created_by' => $paymentData['created_by'] ?? auth()->id(),
                'created_by_ip' => $paymentData['created_by_ip'] ?? request()->ip(),
                'lines' => [
                    [
                        'account_id' => (int) $partyAccountId,
                        'party_id' => $invoice->party_id,
                        'debit' => $amount,
                        'credit' => 0,
                        'description' => "Payment against Invoice #{$invoice->invoice_number}",
                    ],
                    [
                        'account_id' => $cashBank->id,
                        'debit' => 0,
                        'credit' => $amount,
                        'description' => "Paid for Invoice #{$invoice->invoice_number}",
                    ],
                ],
            ]);

            // NEW: Create payment-invoice mapping
            $paymentVoucher = Voucher::query()
                ->where('purchase_invoice_id', $invoice->id)
                ->where('voucher_type', 'payment')
                ->where('status', 'posted')
                ->latest('id')
                ->first();

            if ($paymentVoucher) {
                // Check if mappings provided (for multi-invoice payments in future)
                $mappings = $paymentData['mappings'] ?? null;

                if ($mappings && is_array($mappings)) {
                    // Multi-invoice mapping (future feature)
                    $this->paymentMappingService->createExplicitMappings($paymentVoucher->id, $mappings);
                } else {
                    // Single invoice mapping (current behavior)
                    $this->paymentMappingService->autoMapPayment(
                        $paymentVoucher->id,
                        'purchase',
                        [['invoice_id' => $invoice->id, 'amount' => $amount, 'invoice_type' => 'purchase']]
                    );
                }
            }

            $invoice->recordPayment($amount);

            return $invoice->fresh(['party', 'financialYear', 'lines.item', 'lines.taxRate', 'lines.account']);
        });
    }

    /**
     * Record one payment against multiple purchase invoices of the same party (Tally-style bill allocation).
     *
     * @param  array<int, array{invoice_id:int,amount:float}>  $allocations
     */
    public function recordMultiInvoicePayment(
        int $partyId,
        array $allocations,
        int $cashBankAccountId,
        string $paymentDate,
        array $meta = []
    ): array {
        return DB::transaction(function () use ($partyId, $allocations, $cashBankAccountId, $paymentDate, $meta) {
            if (empty($allocations)) {
                throw new \RuntimeException('Select at least one invoice to record payment against.');
            }

            $companyId = (int) ($meta['company_id'] ?? auth()->user()->company_id);
            $invoiceIds = array_column($allocations, 'invoice_id');

            $invoices = PurchaseInvoice::query()
                ->whereIn('id', $invoiceIds)
                ->where('party_id', $partyId)
                ->where('company_id', $companyId)
                ->with('party.account')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($invoices->count() !== count(array_unique($invoiceIds))) {
                throw new \RuntimeException('One or more selected invoices were not found for this party.');
            }

            $totalAmount = 0.0;
            foreach ($allocations as $allocation) {
                $invoice = $invoices->get((int) $allocation['invoice_id']);
                $amount = round((float) ($allocation['amount'] ?? 0), 2);

                if ($invoice->status === 'cancelled') {
                    throw new \RuntimeException("Invoice #{$invoice->invoice_number} is cancelled.");
                }
                if ($amount <= 0) {
                    throw new \RuntimeException("Allocation amount for Invoice #{$invoice->invoice_number} must be greater than zero.");
                }
                if ($amount > round((float) $invoice->balance_due, 2) + 0.009) {
                    throw new \RuntimeException("Allocation for Invoice #{$invoice->invoice_number} exceeds its balance due.");
                }

                $totalAmount += $amount;
            }

            $totalAmount = round($totalAmount, 2);
            if ($totalAmount <= 0) {
                throw new \RuntimeException('Total payment amount must be greater than zero.');
            }

            $firstInvoice = $invoices->first();
            $settlementFy = $this->periodLockService->assertWritable($companyId, $paymentDate);

            $cashBank = \App\Models\Account::query()
                ->where('company_id', $companyId)
                ->where('id', $cashBankAccountId)
                ->where('is_active', true)
                ->first();

            if (!$cashBank || !$cashBank->isCashBankOd()) {
                throw new \RuntimeException('Select a valid Cash / Bank / OD account.');
            }

            $available = $this->ledgerService->getAvailablePaymentBalance(
                $cashBank->id,
                $companyId,
                (int) $settlementFy->id
            );
            if ($available !== null && $totalAmount > $available + 0.009) {
                throw new \RuntimeException(
                    'Insufficient balance in ' . $cashBank->account_name . '. Available: ₹' . number_format($available, 2)
                );
            }

            $partyAccountId = $firstInvoice->party?->account_id
                ?: \App\Models\Account::query()
                ->where('company_id', $companyId)
                ->where('account_code', \App\Models\Account::CODE_AP)
                ->value('id');
            if (!$partyAccountId) {
                throw new \RuntimeException('Accounts Payable control account is missing.');
            }

            $invoiceNumbers = $invoices->pluck('invoice_number')->implode(', ');

            $payment = $this->voucherService->create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'company_id' => $companyId,
                'financial_year_id' => $settlementFy->id,
                'party_id' => $partyId,
                'voucher_type' => 'payment',
                'voucher_date' => $paymentDate,
                'narration' => "Payment against Purchase Invoices #{$invoiceNumbers}",
                'created_by' => $meta['created_by'] ?? auth()->id(),
                'created_by_ip' => $meta['created_by_ip'] ?? request()->ip(),
                'lines' => [
                    [
                        'account_id' => (int) $partyAccountId,
                        'party_id' => $partyId,
                        'debit' => $totalAmount,
                        'credit' => 0,
                        'description' => "Payment against Invoices #{$invoiceNumbers}",
                    ],
                    [
                        'account_id' => $cashBank->id,
                        'debit' => 0,
                        'credit' => $totalAmount,
                        'description' => "Paid for Invoices #{$invoiceNumbers}",
                    ],
                ],
            ]);

            $mappings = array_map(fn (array $allocation) => [
                'invoice_id' => (int) $allocation['invoice_id'],
                'amount' => round((float) $allocation['amount'], 2),
                'invoice_type' => 'purchase',
                'reference_number' => $allocation['reference_number'] ?? null,
            ], $allocations);
            $this->paymentMappingService->createExplicitMappings($payment->id, $mappings);

            $updatedInvoices = collect();
            foreach ($allocations as $allocation) {
                $invoice = $invoices->get((int) $allocation['invoice_id']);
                $invoice->recordPayment(round((float) $allocation['amount'], 2));
                $updatedInvoices->push($invoice->fresh());
            }

            return [
                'voucher' => $payment->fresh(['party', 'lines.account']),
                'invoices' => $updatedInvoices,
            ];
        });
    }

    /**
     * Cancel a purchase invoice: reverse settlements, cancel posting voucher, reverse stock.
     */
    public function cancel(int $id): PurchaseInvoice
    {
        return DB::transaction(function () use ($id) {
            $invoice = PurchaseInvoice::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if (!$invoice) {
                throw new \RuntimeException('Purchase invoice not found.');
            }

            if ($invoice->status === 'cancelled') {
                throw new \RuntimeException('This purchase invoice is already cancelled.');
            }

            $this->periodLockService->assertWritable(
                (int) $invoice->company_id,
                $invoice->invoice_date,
                $invoice->financial_year_id ? (int) $invoice->financial_year_id : null
            );

            $vouchers = Voucher::query()
                ->where('purchase_invoice_id', $invoice->id)
                ->where('status', 'posted')
                ->orderByRaw("CASE WHEN voucher_type = 'payment' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();

            foreach ($vouchers as $voucher) {
                $this->voucherService->cancel((int) $voucher->id, true);
            }

            $itemLines = $invoice->lines()->where('line_type', 'item')->get();
            $this->itemService->applyStockFromLines($itemLines, 'out');

            $invoice->update([
                'status' => 'cancelled',
                'amount_paid' => 0,
                'balance_due' => 0,
            ]);

            return $invoice->fresh(['party', 'financialYear', 'lines.item', 'lines.taxRate', 'lines.account']);
        });
    }

    /**
     * Delete a purchase invoice.
     */
    public function delete(int $id): bool
    {
        $invoice = PurchaseInvoice::findOrFail($id);
        if ($invoice->vouchers()->exists() || in_array($invoice->status, ['verified', 'paid', 'partial', 'cancelled'], true)) {
            throw new \RuntimeException(
                'A posted purchase invoice cannot be deleted because accounting entries exist. Cancel it instead.'
            );
        }

        return DB::transaction(function () use ($invoice) {
            $oldLines = $invoice->lines()->where('line_type', 'item')->get();
            $this->itemService->applyStockFromLines($oldLines, 'out');
            return (bool) $invoice->delete();
        });
    }

    /**
     * Get overdue invoices.
     */
    public function getOverdue(int $companyId): Collection
    {
        return PurchaseInvoice::where('company_id', $companyId)->overdue()->get();
    }

    /**
     * Generate next invoice number: PUR-202627/0001
     */
    public function generateInvoiceNumber(int $companyId, int $financialYearId): string
    {
        $fy = FinancialYear::find($financialYearId);
        $fyCode = $fy?->code() ?? now()->format('Y') . now()->copy()->addYear()->format('y');
        $needle = 'PUR-' . $fyCode . '/';

        $numbers = PurchaseInvoice::withTrashed()
            ->where('company_id', $companyId)
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

    /**
     * Generate voucher from purchase invoice via VoucherService.
     */
    public function generateVoucher(PurchaseInvoice $invoice, ?int $accountId = null): ?Voucher
    {
        return DB::transaction(function () use ($invoice, $accountId) {
            $existing = Voucher::query()
                ->where('purchase_invoice_id', $invoice->id)
                ->where('voucher_type', 'expense')
                ->where('status', 'posted')
                ->first();

            if ($existing) {
                return $existing->load(['party', 'lines.account']);
            }

            $lines = $this->invoiceAccountingService->buildPurchaseVoucherLines($invoice, $accountId);

            $voucher = $this->voucherService->createFromPurchaseInvoice([
                'company_id' => $invoice->company_id,
                'financial_year_id' => $invoice->financial_year_id,
                'party_id' => $invoice->party_id,
                'voucher_date' => $invoice->invoice_date,
                'narration' => filled($invoice->notes)
                    ? (string) $invoice->notes
                    : "Purchase Invoice #{$invoice->invoice_number}",
                'total' => $invoice->total,
                'purchase_invoice_id' => $invoice->id,
                'created_by' => $invoice->created_by,
            ], $lines);

            $invoice->update(['status' => 'verified']);

            return $voucher;
        });
    }
}
