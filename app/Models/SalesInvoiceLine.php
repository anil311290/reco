<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesInvoiceLine extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'sales_invoice_id',
        'line_type',
        'item_id',
        'account_id',
        'tax_rate_id',
        'description',
        'quantity',
        'unit_price',
        'discount_percentage',
        'discount_amount',
        'tax_amount',
        'total',
        'sort_order',
        'version',
        'synced_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * Calculate line total.
     */
    public function calculateTotal(): void
    {
        $base = $this->quantity * $this->unit_price;
        $this->discount_amount = $base * ($this->discount_percentage / 100);
        $afterDiscount = $base - $this->discount_amount;

        if ($this->taxRate) {
            $this->tax_amount = $this->taxRate->calculateTax((float) $afterDiscount);
        }

        $this->total = $afterDiscount + $this->tax_amount;
        $this->save();
    }
}
