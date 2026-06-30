<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncQueue extends Model
{
    use HasFactory, HasUuid;

    protected $fillable = [
        'uuid',
        'table_name',
        'record_uuid',
        'operation',
        'payload',
        'metadata',
        'status',
        'retry_count',
        'max_retries',
        'error_message',
        'processed_at',
        'device_id',
        'user_id',
        'company_id',
        'local_version',
        'server_version',
        'conflict_resolution',
    ];

    protected $casts = [
        'payload' => 'array',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // ── Scopes ────────────────────────────────────────────────

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForTable($query, string $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeForDevice($query, string $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeRetryable($query)
    {
        return $query->where('status', 'failed')
            ->whereColumn('retry_count', '<', 'max_retries');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('created_at', 'asc');
    }

    // ── Methods ───────────────────────────────────────────────

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(): void
    {
        $this->update(['status' => 'completed', 'processed_at' => now()]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'retry_count' => $this->retry_count + 1,
        ]);
    }

    public function isRetryable(): bool
    {
        return $this->status === 'failed' && $this->retry_count < $this->max_retries;
    }

    public function hasConflict(): bool
    {
        return $this->local_version !== null
            && $this->server_version !== null
            && $this->local_version !== $this->server_version;
    }
}
