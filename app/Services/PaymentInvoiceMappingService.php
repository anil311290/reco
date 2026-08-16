<?php

namespace App\Services;

use App\Interfaces\PaymentInvoiceMappingRepositoryInterface;
use App\Models\PaymentInvoiceMapping;
use App\Models\Voucher;
use Illuminate\Support\Collection;

class PaymentInvoiceMappingService
{
    protected PaymentInvoiceMappingRepositoryInterface $repository;

    public function __construct(PaymentInvoiceMappingRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Automatically map a payment to one or more invoices.
     * If mappings array is not provided, attempts intelligent auto-mapping.
     */
    public function autoMapPayment(
        int $paymentVoucherId,
        string $invoiceType,
        ?array $mappings = null
    ): Collection {
        $voucher = Voucher::findOrFail($paymentVoucherId);
        $totalAmount = (float) $voucher->total_debit;

        // If mappings provided explicitly, use them
        if ($mappings && !empty($mappings)) {
            return $this->createExplicitMappings($paymentVoucherId, $mappings);
        }

        // Otherwise, try intelligent auto-mapping
        // Default: attempt to map to the first non-deleted invoice of this type
        return collect(); // Return empty collection if no auto-mapping possible
    }

    /**
     * Create explicit mappings from provided array. Each mapping represents an amount
     * that has already been paid (voucher posted), so it is immediately marked as settled.
     */
    public function createExplicitMappings(int $paymentVoucherId, array $mappings): Collection
    {
        $createdMappings = collect();

        foreach ($mappings as $mapping) {
            $invoiceId = (int) ($mapping['invoice_id'] ?? 0);
            $amount = (float) ($mapping['amount'] ?? 0);
            $invoiceType = $mapping['invoice_type'] ?? 'sales';
            $referenceNumber = $mapping['reference_number'] ?? null;

            if ($invoiceId && $amount > 0) {
                $createdMapping = $this->repository->createMapping(
                    $paymentVoucherId,
                    $invoiceType,
                    $invoiceId,
                    $amount,
                    $referenceNumber
                );
                $createdMapping = $this->repository->updateMappingSettlement($createdMapping, $amount);
                $createdMappings->push($createdMapping);
            }
        }

        return $createdMappings;
    }

    /**
     * Settle a payment by updating mapped invoices' amounts.
     */
    public function settlePayment(
        int $paymentVoucherId,
        float $settledAmount
    ): array {
        $mappings = PaymentInvoiceMapping::query()
            ->where('payment_voucher_id', $paymentVoucherId)
            ->where('status', '!=', 'reversed')
            ->get();

        if ($mappings->isEmpty()) {
            return ['success' => false, 'message' => 'No mappings found for this payment'];
        }

        $summary = [
            'total_allocated' => 0,
            'total_settled' => 0,
            'mappings_updated' => 0,
            'details' => [],
        ];

        foreach ($mappings as $mapping) {
            $summary['total_allocated'] += (float) $mapping->amount_allocated;

            // If this is the last mapping or enough has been settled, settle remainder to it
            $amountForThisMapping = min(
                (float) $mapping->amount_allocated,
                $settledAmount
            );

            if ($amountForThisMapping > 0) {
                $this->repository->updateMappingSettlement($mapping, $amountForThisMapping);
                $settledAmount -= $amountForThisMapping;
                $summary['total_settled'] += $amountForThisMapping;
                $summary['mappings_updated']++;
                $summary['details'][] = [
                    'invoice_id' => $mapping->invoice_id,
                    'invoice_type' => $mapping->invoice_type,
                    'amount_settled' => $amountForThisMapping,
                    'status' => $mapping->status,
                ];
            }
        }

        return $summary;
    }

    /**
     * Reverse all mappings for a payment voucher (called when voucher is cancelled).
     */
    public function reverseAllMappings(int $paymentVoucherId): int
    {
        $mappings = PaymentInvoiceMapping::query()
            ->where('payment_voucher_id', $paymentVoucherId)
            ->where('status', '!=', 'reversed')
            ->get();

        $reversedCount = 0;
        foreach ($mappings as $mapping) {
            $this->repository->reverseMapping($mapping);
            $reversedCount++;
        }

        return $reversedCount;
    }

    /**
     * Get settlement summary for a payment voucher.
     */
    public function getSettlementSummary(int $paymentVoucherId): array
    {
        $mappings = PaymentInvoiceMapping::query()
            ->where('payment_voucher_id', $paymentVoucherId)
            ->where('status', '!=', 'reversed')
            ->get();

        $totalAllocated = $this->repository->getTotalAllocated($paymentVoucherId);
        $totalSettled = $this->repository->getTotalSettled($paymentVoucherId);

        return [
            'payment_voucher_id' => $paymentVoucherId,
            'total_allocated' => $totalAllocated,
            'total_settled' => $totalSettled,
            'outstanding' => max(0, $totalAllocated - $totalSettled),
            'fully_settled' => count($mappings->where('status', 'full')) . ' of ' . $mappings->count(),
            'partially_settled' => count($mappings->where('status', 'partial')),
            'pending' => count($mappings->where('status', 'pending')),
            'mappings_count' => $mappings->count(),
            'invoices_settled' => $mappings->pluck('invoice_id', 'invoice_type')->toArray(),
        ];
    }

    /**
     * Get invoice settlement details (all payments that settled it).
     */
    public function getInvoiceSettlementDetails(string $invoiceType, int $invoiceId): array
    {
        $mappings = PaymentInvoiceMapping::query()
            ->where('invoice_type', $invoiceType)
            ->where('invoice_id', $invoiceId)
            ->where('status', '!=', 'reversed')
            ->with('paymentVoucher')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSettled = (float) $mappings->sum('amount_settled');
        $outstandingInvoiceBalance = 0;

        if ($invoiceType === 'sales') {
            $invoice = \App\Models\SalesInvoice::find($invoiceId);
            $outstandingInvoiceBalance = $invoice ? (float) $invoice->balance_due : 0;
        } else {
            $invoice = \App\Models\PurchaseInvoice::find($invoiceId);
            $outstandingInvoiceBalance = $invoice ? (float) $invoice->balance_due : 0;
        }

        return [
            'invoice_type' => $invoiceType,
            'invoice_id' => $invoiceId,
            'total_settled' => $totalSettled,
            'outstanding_balance' => $outstandingInvoiceBalance,
            'settlements' => $mappings->map(fn ($m) => [
                'payment_voucher_id' => $m->payment_voucher_id,
                'voucher_number' => $m->paymentVoucher->voucher_number ?? 'N/A',
                'voucher_date' => $m->paymentVoucher->voucher_date ?? null,
                'amount_allocated' => (float) $m->amount_allocated,
                'amount_settled' => (float) $m->amount_settled,
                'status' => $m->status,
            ])->toArray(),
        ];
    }

    /**
     * Get payment settlement details (all invoices settled by this payment).
     */
    public function getPaymentSettlementDetails(int $paymentVoucherId): array
    {
        $mappings = PaymentInvoiceMapping::query()
            ->where('payment_voucher_id', $paymentVoucherId)
            ->where('status', '!=', 'reversed')
            ->orderBy('created_at')
            ->get();

        $voucher = Voucher::find($paymentVoucherId);
        $voucherTotal = $voucher ? (float) $voucher->total_debit : 0;

        $invoiceBreakdown = $mappings->groupBy('invoice_type')->map(fn ($group) => [
            'type' => $group->first()->invoice_type,
            'count' => $group->count(),
            'total_allocated' => (float) $group->sum('amount_allocated'),
            'total_settled' => (float) $group->sum('amount_settled'),
        ])->values()->toArray();

        return [
            'payment_voucher_id' => $paymentVoucherId,
            'voucher_number' => $voucher->voucher_number ?? 'N/A',
            'voucher_date' => $voucher->voucher_date ?? null,
            'voucher_total' => $voucherTotal,
            'invoices_settled_count' => $mappings->count(),
            'invoice_breakdown' => $invoiceBreakdown,
            'mappings' => $mappings->map(fn ($m) => [
                'invoice_type' => $m->invoice_type,
                'invoice_id' => $m->invoice_id,
                'amount_allocated' => (float) $m->amount_allocated,
                'amount_settled' => (float) $m->amount_settled,
                'status' => $m->status,
            ])->toArray(),
        ];
    }

    /**
     * Get settlement audit report for a company.
     */
    public function getSettlementAuditReport(
        int $companyId,
        ?\Carbon\Carbon $fromDate = null,
        ?\Carbon\Carbon $toDate = null
    ): array {
        $query = PaymentInvoiceMapping::query()
            ->where('company_id', $companyId)
            ->with('paymentVoucher');

        if ($fromDate) {
            $query->where('created_at', '>=', $fromDate->startOfDay());
        }

        if ($toDate) {
            $query->where('created_at', '<=', $toDate->endOfDay());
        }

        $mappings = $query->orderBy('created_at', 'desc')->get();

        return [
            'company_id' => $companyId,
            'from_date' => $fromDate?->toDateString(),
            'to_date' => $toDate?->toDateString(),
            'total_records' => $mappings->count(),
            'summary_by_status' => $mappings->groupBy('status')->map(fn ($group) => [
                'status' => $group->first()->status,
                'count' => $group->count(),
                'total_allocated' => (float) $group->sum('amount_allocated'),
                'total_settled' => (float) $group->sum('amount_settled'),
            ])->values()->toArray(),
            'records' => $mappings->map(fn ($m) => [
                'id' => $m->id,
                'payment_voucher_id' => $m->payment_voucher_id,
                'voucher_number' => $m->paymentVoucher->voucher_number ?? 'N/A',
                'voucher_date' => $m->paymentVoucher->voucher_date?->toDateString(),
                'invoice_type' => $m->invoice_type,
                'invoice_id' => $m->invoice_id,
                'amount_allocated' => (float) $m->amount_allocated,
                'amount_settled' => (float) $m->amount_settled,
                'status' => $m->status,
                'created_at' => $m->created_at->toDateString(),
            ])->toArray(),
        ];
    }
}
