<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
{
    use HasFactory, HasAuditFields, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'account_id',
        'bank_name',
        'branch_name',
        'account_number',
        'ifsc_code',
        'account_holder_name',
        'account_type',
        'opening_balance',
        'opening_date',
        'upi_id',
        'is_default',
        'is_active',
        'remarks',
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
        'opening_balance' => 'decimal:2',
        'opening_date' => 'date',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the default bank account for a company.
     */
    public static function getDefault(?int $companyId): ?self
    {
        return static::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
