<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivableReminder extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'sales_invoice_id',
        'party_id',
        'due_date',
        'reminder_date',
        'reminder_sequence',
        'channel',
        'template_name',
        'phone_number',
        'email_address',
        'message_content',
        'status',
        'failure_reason',
        'sent_at',
        'invoice_total',
        'amount_paid',
        'balance_due',
        'days_overdue',
        'type',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'version',
        'synced_at',
    ];

    protected $casts = [
        'due_date' => 'date',
        'reminder_date' => 'date',
        'sent_at' => 'datetime',
        'invoice_total' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_due' => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeDueToday($query)
    {
        return $query->where('reminder_date', today())->where('status', 'pending');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', today())->whereIn('status', ['pending', 'scheduled']);
    }

    // ── Methods ───────────────────────────────────────────────

    public function markAsSent(): void
    {
        $this->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update(['status' => 'failed', 'failure_reason' => $reason]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isOverdue(): bool
    {
        return $this->due_date->isPast() && in_array($this->status, ['pending', 'scheduled']);
    }
}
