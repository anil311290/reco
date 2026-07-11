<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use Illuminate\Database\Eloquent\Model;

class LoginHistory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'company_id',
        'ip_address',
        'user_agent',
        'device_type',
        'device_name',
        'device_os',
        'browser',
        'location',
        'status',
        'failure_reason',
        'session_id',
        'logged_out_at',
        'created_at',
    ];

    protected $casts = [
        'logged_out_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
