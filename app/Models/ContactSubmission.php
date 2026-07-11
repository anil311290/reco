<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ContactSubmission extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'admin_notes',
        'read_at',
        'replied_at',
        'replied_by',
        'replied_by_id',
        'created_by_ip',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
    ];

    public function repliedByUser()
    {
        return $this->belongsTo(User::class, 'replied_by_id');
    }

    public function markAsRead(): void
    {
        if ($this->status === 'new') {
            $this->update(['status' => 'read', 'read_at' => now()]);
        }
    }

    public function markAsReplied(?int $userId = null): void
    {
        $this->update([
            'status' => 'replied',
            'replied_at' => now(),
            'replied_by_id' => $userId,
        ]);
    }

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
