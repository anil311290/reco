<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Theme extends Model
{
    use HasFactory, HasAuditFields, HasUuid;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'primary_color',
        'secondary_color',
        'accent_color',
        'sidebar_color',
        'header_color',
        'text_color',
        'bg_color',
        'font_family',
        'logo_url',
        'favicon_url',
        'login_bg_url',
        'dark_mode',
        'custom_css',
        'is_active',
        'is_default',
        'version',
        'synced_at',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
    ];

    protected $casts = [
        'dark_mode' => 'boolean',
        'custom_css' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get active theme for a company, or default if none exists.
     */
    public static function getForCompany(?int $companyId): self
    {
        if ($companyId) {
            $theme = static::where('company_id', $companyId)->where('is_active', true)->first();
            if ($theme) return $theme;
        }

        return static::where('is_default', true)->first() ?? static::first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
