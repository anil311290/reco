<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

    protected $fillable = [
        'uuid',
        'client_name',
        'company_name',
        'designation',
        'testimonial',
        'avatar',
        'rating',
        'is_featured',
        'is_active',
        'sort_order',
        'version',
        'synced_at',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }
}
