<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPayment extends Model
{
    use FormatsHumanReadableDates, HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'subscription_id',
        'invoice_id',
        'razorpay_payment_id',
        'razorpay_order_id',
        'amount',
        'currency',
        'status',
        'payment_method',
        'gateway_response',
        'paid_at',
        'failure_reason',
        'version',
        'synced_at',
        'created_by_ip',
        'updated_by_ip',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'gateway_response' => 'array',
        'paid_at' => 'datetime',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function invoice()
    {
        return $this->belongsTo(SubscriptionInvoice::class, 'invoice_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
