<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'user_id',
        'assigned_to',
        'ticket_number',
        'subject',
        'category',
        'priority',
        'status',
        'last_message_at',
        'closed_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)->orderBy('created_at');
    }

    public function publicMessages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class)
            ->where('is_internal', false)
            ->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return !in_array($this->status, ['resolved', 'closed'], true);
    }

    public static function generateTicketNumber(int $companyId): string
    {
        $count = static::where('company_id', $companyId)->count() + 1;

        return 'TKT-' . str_pad((string) $companyId, 4, '0', STR_PAD_LEFT) . '-' . str_pad((string) $count, 5, '0', STR_PAD_LEFT);
    }
}
