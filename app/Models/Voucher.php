<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Voucher extends Model
{
    use FormatsHumanReadableDates, HasFactory, HasAuditFields, HasUuid, HasVersioning, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'party_id',
        'sales_invoice_id',
        'purchase_invoice_id',
        'voucher_number',
        'voucher_type',
        'voucher_date',
        'narration',
        'total_debit',
        'total_credit',
        'status',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'deleted_by',
        'deleted_by_id',
    ];

    protected $casts = [
        'voucher_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
    ];

    /**
     * Get the company that owns the voucher.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the financial year for the voucher.
     */
    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    /**
     * Get the party for the voucher.
     */
    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function purchaseInvoice()
    {
        return $this->belongsTo(PurchaseInvoice::class);
    }

    /**
     * Get the voucher lines.
     */
    public function lines()
    {
        return $this->hasMany(VoucherLine::class);
    }

    /**
     * Scope: Get vouchers by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('voucher_type', $type);
    }

    /**
     * Scope: Get vouchers by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get posted vouchers
     */
    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    /**
     * Check if voucher is balanced
     */
    public function isBalanced(): bool
    {
        return $this->total_debit == $this->total_credit;
    }

    /**
     * Get voucher type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->voucher_type) {
            'income' => 'Income',
            'expense' => 'Expense',
            'receipt' => 'Receipt',
            'payment' => 'Payment',
            'journal' => 'Journal',
            'adjustment' => 'Adjustment',
            default => ucfirst($this->voucher_type),
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'draft' => 'bg-warning',
            'posted' => 'bg-success',
            'cancelled' => 'bg-danger',
            default => 'bg-secondary',
        };
    }

    /**
     * Generate next voucher number (includes soft-deleted rows so unique keys stay free).
     */
    public static function generateNumber(string $type, int $companyId, int $financialYearId): string
    {
        $prefix = match ($type) {
            'income' => 'INC',
            'expense' => 'EXP',
            'receipt' => 'RCT',
            'payment' => 'PAY',
            'journal' => 'JRN',
            'adjustment' => 'ADJ',
            default => 'VCH',
        };

        $numbers = static::withTrashed()
            ->where('company_id', $companyId)
            ->where('voucher_number', 'like', $prefix . '%')
            ->pluck('voucher_number');

        $max = 0;
        foreach ($numbers as $number) {
            $seq = (int) substr((string) $number, strlen($prefix));
            if ($seq > $max) {
                $max = $seq;
            }
        }

        return $prefix . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Post the voucher
     */
    public function post(): bool
    {
        if (!$this->isBalanced()) {
            throw new \Exception('Voucher is not balanced. Total debit must equal total credit.');
        }

        return $this->update(['status' => 'posted']);
    }

    /**
     * Cancel the voucher
     */
    public function cancel(): bool
    {
        return $this->update(['status' => 'cancelled']);
    }
}
