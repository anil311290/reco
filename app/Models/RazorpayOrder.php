<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RazorpayOrder extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'subscription_id',
        'razorpay_order_id',
        'amount',
        'currency',
        'status',
        'attempts',
        'notes',
        'gateway_response',
        'receipt',
        'created_by_ip',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'notes' => 'array',
        'gateway_response' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }
}
