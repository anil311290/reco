<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'lifetime_price',
        'currency',
        'trial_days',
        'max_users',
        'max_transactions',
        'max_accounts',
        'max_parties',
        'features',
        'sort_order',
        'is_active',
        'is_default',
        'is_visible',
        'version',
        'synced_at',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'lifetime_price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function getPrice(string $cycle): float
    {
        if ($cycle === 'yearly') return (float) $this->yearly_price;
        if ($cycle === 'lifetime') return (float) $this->lifetime_price;
        return (float) $this->monthly_price;
    }

    /**
     * Get pricing display for this plan.
     */
    public function pricingDisplay()
    {
        return $this->hasOne(PricingDisplay::class, 'plan_id');
    }

    /**
     * Scope: Active plans
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Visible on pricing page
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }
}
