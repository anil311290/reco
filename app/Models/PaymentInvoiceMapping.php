<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentInvoiceMapping extends Model
{
    use HasFactory, HasAuditFields, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'payment_voucher_id',
        'invoice_type',
        'invoice_id',
        'invoice_original_balance',
        'amount_allocated',
        'amount_settled',
        'status',
        'notes',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
    ];

    protected $casts = [
        'invoice_original_balance' => 'decimal:2',
        'amount_allocated' => 'decimal:2',
        'amount_settled' => 'decimal:2',
    ];

    /**
     * Get the payment/receipt voucher.
     */
    public function paymentVoucher()
    {
        return $this->belongsTo(Voucher::class, 'payment_voucher_id');
    }

    /**
     * Get the associated invoice (polymorphic-style).
     */
    public function getInvoice()
    {
        return $this->invoice_type === 'sales'
            ? SalesInvoice::find($this->invoice_id)
            : PurchaseInvoice::find($this->invoice_id);
    }

    /**
     * Check if settlement is fully completed.
     */
    public function isFullySettled(): bool
    {
        return $this->status === 'full' && (float) $this->amount_settled >= (float) $this->amount_allocated;
    }

    /**
     * Check if settlement is partially completed.
     */
    public function isPartiallySettled(): bool
    {
        return $this->status === 'partial' && (float) $this->amount_settled > 0;
    }

    /**
     * Check if settlement is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending' && (float) $this->amount_settled === 0.0;
    }

    /**
     * Get outstanding amount for this mapping.
     */
    public function getOutstandingAmount(): float
    {
        return (float) $this->amount_allocated - (float) $this->amount_settled;
    }

    /**
     * Scope: Get active mappings (not reversed).
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'reversed');
    }

    /**
     * Scope: Get mappings by payment voucher.
     */
    public function scopeByPaymentVoucher($query, int $voucherId)
    {
        return $query->where('payment_voucher_id', $voucherId);
    }

    /**
     * Scope: Get mappings by invoice.
     */
    public function scopeByInvoice($query, string $type, int $invoiceId)
    {
        return $query->where('invoice_type', $type)->where('invoice_id', $invoiceId);
    }

    /**
     * Scope: Get mappings by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
};
