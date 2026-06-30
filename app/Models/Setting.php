<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'company_id',
        'group',
        'key',
        'value',
        'type',
        'description',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
    ];

    /**
     * Get the company that owns the setting.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope: Get settings by group
     */
    public function scopeByGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    /**
     * Scope: Get settings by company
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get setting value by key
     */
    public static function getValue(string $key, $default = null, ?int $companyId = null)
    {
        $setting = static::where('key', $key)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->first();

        return $setting ? $setting->value : $default;
    }

    /**
     * Set setting value
     */
    public static function setValue(string $key, $value, ?int $companyId = null, string $group = 'general'): void
    {
        static::updateOrCreate(
            [
                'company_id' => $companyId,
                'group' => $group,
                'key' => $key,
            ],
            [
                'value' => $value,
                'updated_by' => auth()->id(),
                'updated_by_ip' => request()->ip(),
            ]
        );
    }

    /**
     * Get all settings as key-value pair
     */
    public static function getAll(?int $companyId = null): array
    {
        return static::when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->pluck('value', 'key')
            ->toArray();
    }

    /**
     * Get all settings by group as key-value pair
     */
    public static function getByGroup(string $group, ?int $companyId = null): array
    {
        return static::where('group', $group)
            ->when($companyId, function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->pluck('value', 'key')
            ->toArray();
    }
}
