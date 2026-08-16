<?php

namespace App\Interfaces;

use App\Models\PaymentInvoiceMapping;

interface PaymentInvoiceMappingRepositoryInterface
{
    /**
     * Create a new payment-invoice mapping.
     */
    public function createMapping(
        int $paymentVoucherId,
        string $invoiceType,
        int $invoiceId,
        float $amountAllocated
    ): PaymentInvoiceMapping;

    /**
     * Get all mappings for a payment voucher.
     */
    public function getMappingsByPaymentVoucher(int $voucherId, bool $activeOnly = true): array;

    /**
     * Get all mappings for an invoice.
     */
    public function getMappingsByInvoice(string $type, int $invoiceId, bool $activeOnly = true): array;

    /**
     * Update the settlement amount for a mapping.
     */
    public function updateMappingSettlement(
        PaymentInvoiceMapping $mapping,
        float $settledAmount
    ): PaymentInvoiceMapping;

    /**
     * Mark a mapping as reversed.
     */
    public function reverseMapping(PaymentInvoiceMapping $mapping): PaymentInvoiceMapping;

    /**
     * Delete a mapping.
     */
    public function deleteMapping(PaymentInvoiceMapping $mapping): bool;

    /**
     * Get total amount allocated for a payment voucher.
     */
    public function getTotalAllocated(int $voucherId): float;

    /**
     * Get total amount settled for a payment voucher.
     */
    public function getTotalSettled(int $voucherId): float;
}
