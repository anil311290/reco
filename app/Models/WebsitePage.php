<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WebsitePage extends Model
{
    use HasFactory, HasAuditFields, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'meta_title',
        'meta_description',
        'content',
        'template',
        'status',
        'show_in_nav',
        'nav_order',
        'version',
        'synced_at',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'deleted_by',
        'deleted_by_id',
    ];

    protected $casts = [
        'show_in_nav' => 'boolean',
    ];

    /**
     * Get the route key for route model binding.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeNavItems($query)
    {
        return $query->where('show_in_nav', true)
            ->where('status', 'published')
            ->orderBy('nav_order');
    }

    public function scopeByTemplate($query, string $template)
    {
        return $query->where('template', $template);
    }
}
