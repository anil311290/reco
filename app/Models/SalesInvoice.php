<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesInvoice extends Model
{
    use FormatsHumanReadableDates, HasFactory, HasAuditFields, HasUuid, HasVersioning, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'party_id',
        'account_id',
        'invoice_number',
        'invoice_date',
        'due_date',
        'reference_number',
        'notes',
        'payment_terms',
        'delivery_terms',
        'subtotal',
        'discount_amount',
        'discount_percentage',
        'tax_amount',
        'total',
        'amount_paid',
        'balance_due',
        'currency',
        'status',
        'is_recurring',
        'version',
        'synced_at',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'deleted_by',
        'deleted_by_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
        'is_recurring' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function lines()
    {
        return $this->hasMany(SalesInvoiceLine::class)->orderBy('sort_order');
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class, 'sales_invoice_id');
    }

    /**
     * Calculate totals from line items.
     */
    public function calculateTotals(): void
    {
        $lines = $this->lines()->get();

        $this->subtotal = $lines->sum(fn($line) => $line->quantity * $line->unit_price);
        $this->tax_amount = $lines->sum('tax_amount');
        $lineDiscount = $lines->sum('discount_amount');
        $headerDiscount = $this->subtotal * ($this->discount_percentage / 100);
        $this->discount_amount = $lineDiscount + $headerDiscount;
        $this->total = $this->subtotal - $this->discount_amount + $this->tax_amount;
        $this->balance_due = $this->total - $this->amount_paid;

        // Update status based on payment
        if ($this->amount_paid >= $this->total) {
            $this->status = 'paid';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'partial';
        }

        $this->save();
    }

    /**
     * Mark payment received.
     */
    public function recordPayment(float $amount): void
    {
        $this->increment('amount_paid', $amount);
        $this->decrement('balance_due', $amount);
        $this->refresh();

        if ($this->balance_due <= 0) {
            $this->update(['status' => 'paid', 'balance_due' => 0]);
        } elseif ($this->amount_paid > 0) {
            $this->update(['status' => 'partial']);
        }
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return !in_array($this->status, ['paid', 'cancelled'])
            && $this->due_date
            && $this->due_date->isPast();
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', ['paid', 'cancelled'])
            ->where('due_date', '<', now());
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
