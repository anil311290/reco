<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingDisplay extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

    protected $fillable = [
        'uuid',
        'plan_id',
        'badge',
        'highlight_color',
        'description_short',
        'description_long',
        'features_list',
        'sort_order',
        'is_active',
        'version',
        'synced_at',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
    ];

    protected $casts = [
        'features_list' => 'array',
        'is_active' => 'boolean',
    ];

    public function plan()
    {
        return $this->belongsTo(SubscriptionPlan::class, 'plan_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
