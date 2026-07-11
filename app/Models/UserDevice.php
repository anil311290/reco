<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasUuid;

    protected $fillable = [
        'uuid',
        'user_id',
        'company_id',
        'device_id',
        'device_type',
        'device_name',
        'device_os',
        'push_token',
        'fcm_token',
        'is_active',
        'is_trusted',
        'last_active_at',
        'metadata',
        'version',
        'synced_at',
        'created_by_ip',
        'updated_by_ip',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_trusted' => 'boolean',
        'last_active_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }
}
