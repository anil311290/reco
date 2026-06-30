<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaxRate extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasVersioning, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'tax_code',
        'tax_name',
        'tax_rate',
        'tax_type',
        'tax_category',
        'notes',
        'status',
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
        'tax_rate' => 'decimal:2',
        'tax_type' => 'string',
        'tax_category' => 'string',
    ];

    protected static function booted(): void
    {
        static::creating(function ($taxRate) {
            if (empty($taxRate->tax_type)) {
                $taxRate->tax_type = 'addition';
            }

            if (empty($taxRate->status)) {
                $taxRate->status = 'active';
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }

    /**
     * Calculate tax amount for a given base amount.
     */
    public function calculateTax(float $amount): float
    {
        $tax = round($amount * ($this->tax_rate / 100), 2);
        return $this->tax_type === 'deduction' ? -$tax : $tax;
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function getNameAttribute(): ?string
    {
        return $this->tax_name;
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['tax_name'] = $value;
    }

    public function getCodeAttribute(): ?string
    {
        return $this->tax_code;
    }

    public function setCodeAttribute(?string $value): void
    {
        $this->attributes['tax_code'] = $value;
    }

    public function getRateAttribute(): ?float
    {
        return $this->tax_rate !== null ? (float) $this->tax_rate : null;
    }

    public function setRateAttribute(?float $value): void
    {
        $this->attributes['tax_rate'] = $value;
    }

    public function getCalculationTypeAttribute(): ?string
    {
        return $this->tax_type;
    }

    public function setCalculationTypeAttribute(?string $value): void
    {
        $this->attributes['tax_type'] = $value;
    }

    public function getCategoryAttribute(): ?string
    {
        return $this->tax_category;
    }

    public function setCategoryAttribute(?string $value): void
    {
        $this->attributes['tax_category'] = $value;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function setIsActiveAttribute(bool $value): void
    {
        $this->attributes['status'] = $value ? 'active' : 'inactive';
    }
}
