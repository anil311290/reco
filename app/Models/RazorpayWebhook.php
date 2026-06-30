<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RazorpayWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_type',
        'razorpay_event_id',
        'payload',
        'status',
        'error_message',
        'retry_count',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function isProcessed(): bool
    {
        return $this->status === 'processed';
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'received');
    }
}
