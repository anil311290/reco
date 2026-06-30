<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subscription extends Model
{
    use HasFactory, HasAuditFields, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'plan_id',
        'status',
        'billing_cycle',
        'start_date',
        'trial_end_date',
        'current_period_start',
        'current_period_end',
        'cancelled_at',
        'pause_until',
        'amount',
        'currency',
        'razorpay_subscription_id',
        'metadata',
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
        'start_date' => 'date',
        'trial_end_date' => 'date',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'cancelled_at' => 'date',
        'pause_until' => 'date',
        'amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function invoices()
    {
        return $this->hasMany(SubscriptionInvoice::class);
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function razorpayOrders()
    {
        return $this->hasMany(RazorpayOrder::class);
    }

    /**
     * Check if subscription is active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['trial', 'active']);
    }

    /**
     * Check if subscription is on trial.
     */
    public function isOnTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_end_date && $this->trial_end_date->isFuture();
    }

    /**
     * Check if subscription has expired.
     */
    public function isExpired(): bool
    {
        return $this->current_period_end && $this->current_period_end->isPast();
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['trial', 'active']);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
