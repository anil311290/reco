<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialYear extends Model
{
    use HasFactory, HasAuditFields, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_closed',
        'closed_at',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'deleted_by',
        'deleted_by_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'closed_at' => 'date',
        'is_current' => 'boolean',
        'is_closed' => 'boolean',
    ];

    /**
     * Get the company that owns the financial year.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope: Get current financial year
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope: Get open financial years
     */
    public function scopeOpen($query)
    {
        return $query->where('is_closed', false);
    }

    /**
     * Scope: Get closed financial years
     */
    public function scopeClosed($query)
    {
        return $query->where('is_closed', true);
    }

    /**
     * Check if financial year is active
     */
    public function isActive(): bool
    {
        $today = now()->format('Y-m-d');
        return $this->start_date->format('Y-m-d') <= $today && 
               $this->end_date->format('Y-m-d') >= $today;
    }

    /**
     * Get the current financial year for a company
     */
    public static function getCurrent(?int $companyId = null): ?self
    {
        return static::where('company_id', $companyId)
            ->where('is_current', true)
            ->first();
    }

    /**
     * Close the financial year
     */
    public function close(): bool
    {
        return $this->update([
            'is_closed' => true,
            'is_current' => false,
            'closed_at' => now(),
        ]);
    }

    /**
     * Set as current financial year
     */
    public function setAsCurrent(): bool
    {
        // Remove current flag from other years
        static::where('company_id', $this->company_id)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);

        return $this->update(['is_current' => true]);
    }
}
