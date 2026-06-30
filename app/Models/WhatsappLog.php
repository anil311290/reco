<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappLog extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'party_id',
        'sales_invoice_id',
        'phone_number',
        'template_name',
        'message_content',
        'message_type',
        'status',
        'external_message_id',
        'sent_at',
        'delivered_at',
        'read_at',
        'failure_reason',
        'request_payload',
        'response_metadata',
        'retry_count',
        'created_by',
        'created_by_ip',
        'version',
        'synced_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'read_at' => 'datetime',
        'request_payload' => 'array',
        'response_metadata' => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function salesInvoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForParty($query, int $partyId)
    {
        return $query->where('party_id', $partyId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // ── Methods ───────────────────────────────────────────────

    public function markAsSent(string $externalId): void
    {
        $this->update([
            'status' => 'sent',
            'external_message_id' => $externalId,
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update(['status' => 'delivered', 'delivered_at' => now()]);
    }

    public function markAsRead(): void
    {
        $this->update(['status' => 'read', 'read_at' => now()]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function isRetryable(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }
}
