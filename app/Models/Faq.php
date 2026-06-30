<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

    protected $fillable = [
        'uuid',
        'question',
        'answer',
        'category',
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
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
