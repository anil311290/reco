<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'module',
        'record_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'description',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
    ];

    /**
     * Get the company that owns the audit log.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Get logs by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Get logs by module
     */
    public function scopeByModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope: Get logs by user
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Get logs within date range
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('created_at', [$from, $to]);
    }

    /**
     * Create an audit log entry
     */
    public static function log(
        string $action,
        string $module,
        ?int $recordId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): self {
        return static::create([
            'company_id' => auth()->user()?->company_id,
            'user_id' => auth()->id(),
            'action' => $action,
            'module' => $module,
            'record_id' => $recordId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * Get action label
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            'login' => 'Logged In',
            'logout' => 'Logged Out',
            'status_change' => 'Status Changed',
            default => ucfirst($this->action),
        };
    }

    /**
     * Get action badge class
     */
    public function getActionBadgeClassAttribute(): string
    {
        return match($this->action) {
            'create' => 'bg-success',
            'update' => 'bg-primary',
            'delete' => 'bg-danger',
            'login' => 'bg-info',
            'logout' => 'bg-secondary',
            'status_change' => 'bg-warning',
            default => 'bg-secondary',
        };
    }
}
