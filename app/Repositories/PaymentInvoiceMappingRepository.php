<?php

namespace App\Repositories;

use App\Interfaces\PaymentInvoiceMappingRepositoryInterface;
use App\Models\PaymentInvoiceMapping;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Database\Eloquent\Collection;

class PaymentInvoiceMappingRepository implements PaymentInvoiceMappingRepositoryInterface
{
    /**
     * Create a new payment-invoice mapping.
     */
    public function createMapping(
        int $paymentVoucherId,
        string $invoiceType,
        int $invoiceId,
        float $amountAllocated,
        ?string $referenceNumber = null
    ): PaymentInvoiceMapping {
        // Validate invoice exists and has sufficient balance
        $invoice = $this->getInvoiceModel($invoiceType, $invoiceId);
        if (!$invoice) {
            throw new \Exception("Invoice not found: {$invoiceType} #{$invoiceId}");
        }

        $balanceDue = (float) $invoice->balance_due;
        if ($balanceDue < $amountAllocated) {
            throw new \Exception(
                "Allocation amount (₹{$amountAllocated}) exceeds invoice balance (₹{$balanceDue})"
            );
        }

        // Check for duplicate mapping
        $existing = PaymentInvoiceMapping::query()
            ->where('payment_voucher_id', $paymentVoucherId)
            ->where('invoice_type', $invoiceType)
            ->where('invoice_id', $invoiceId)
            ->where('status', '!=', 'reversed')
            ->first();

        if ($existing) {
            throw new \Exception(
                "Mapping already exists for this payment and invoice"
            );
        }

        return PaymentInvoiceMapping::create([
            'company_id' => $invoice->company_id,
            'payment_voucher_id' => $paymentVoucherId,
            'invoice_type' => $invoiceType,
            'invoice_id' => $invoiceId,
            'invoice_original_balance' => $balanceDue,
            'amount_allocated' => $amountAllocated,
            'amount_settled' => 0,
            'status' => 'pending',
            'reference_number' => $referenceNumber,
            'created_by' => auth()->id(),
            'created_by_ip' => request()->ip(),
        ]);
    }

    /**
     * Get all mappings for a payment voucher.
     */
    public function getMappingsByPaymentVoucher(int $voucherId, bool $activeOnly = true): array
    {
        $query = PaymentInvoiceMapping::query()
            ->where('payment_voucher_id', $voucherId);

        if ($activeOnly) {
            $query->where('status', '!=', 'reversed');
        }

        return $query->with('paymentVoucher')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    /**
     * Get all mappings for an invoice.
     */
    public function getMappingsByInvoice(string $type, int $invoiceId, bool $activeOnly = true): array
    {
        $query = PaymentInvoiceMapping::query()
            ->where('invoice_type', $type)
            ->where('invoice_id', $invoiceId);

        if ($activeOnly) {
            $query->where('status', '!=', 'reversed');
        }

        return $query->with('paymentVoucher')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Update the settlement amount for a mapping.
     */
    public function updateMappingSettlement(
        PaymentInvoiceMapping $mapping,
        float $settledAmount
    ): PaymentInvoiceMapping {
        // Validate settled amount doesn't exceed allocated amount
        if ($settledAmount > (float) $mapping->amount_allocated) {
            throw new \Exception(
                "Settled amount (₹{$settledAmount}) cannot exceed allocated amount (₹{$mapping->amount_allocated})"
            );
        }

        $mapping->amount_settled = $settledAmount;
        
        // Update status based on settlement
        if ($settledAmount >= (float) $mapping->amount_allocated) {
            $mapping->status = 'full';
        } elseif ($settledAmount > 0) {
            $mapping->status = 'partial';
        } else {
            $mapping->status = 'pending';
        }

        $mapping->updated_by = auth()->id();
        $mapping->updated_by_ip = request()->ip();
        $mapping->save();

        return $mapping;
    }

    /**
     * Mark a mapping as reversed.
     */
    public function reverseMapping(PaymentInvoiceMapping $mapping): PaymentInvoiceMapping
    {
        $mapping->status = 'reversed';
        $mapping->updated_by = auth()->id();
        $mapping->updated_by_ip = request()->ip();
        $mapping->save();

        return $mapping;
    }

    /**
     * Delete a mapping.
     */
    public function deleteMapping(PaymentInvoiceMapping $mapping): bool
    {
        return $mapping->forceDelete();
    }

    /**
     * Get total amount allocated for a payment voucher.
     */
    public function getTotalAllocated(int $voucherId): float
    {
        return (float) PaymentInvoiceMapping::query()
            ->where('payment_voucher_id', $voucherId)
            ->where('status', '!=', 'reversed')
            ->sum('amount_allocated');
    }

    /**
     * Get total amount settled for a payment voucher.
     */
    public function getTotalSettled(int $voucherId): float
    {
        return (float) PaymentInvoiceMapping::query()
            ->where('payment_voucher_id', $voucherId)
            ->where('status', '!=', 'reversed')
            ->sum('amount_settled');
    }

    /**
     * Get the invoice model (Sales or Purchase).
     */
    private function getInvoiceModel(string $type, int $id)
    {
        if ($type === 'sales') {
            return SalesInvoice::withTrashed()->find($id);
        }

        return PurchaseInvoice::withTrashed()->find($id);
    }
}
