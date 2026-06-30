<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasVersioning, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'party_code',
        'name',
        'type',
        'mobile',
        'email',
        'address',
        'city',
        'state',
        'country',
        'country_id',
        'state_id',
        'city_id',
        'postal_code',
        'gstin',
        'pan_number',
        'opening_balance',
        'opening_balance_type',
        'opening_date',
        'remarks',
        'is_active',
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
        'is_active' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::creating(function ($party) {
            if (empty($party->party_code)) {
                $party->party_code = self::generateCode($party->type, $party->company_id);
            }
        });

        static::created(function ($party) {
            \App\Models\AuditLog::log(
                'create',
                'parties',
                $party->id,
                null,
                $party->toArray(),
                "Created party {$party->name}"
            );
        });

        static::updated(function ($party) {
            $changes = $party->getChanges();
            if (!empty($changes)) {
                \App\Models\AuditLog::log(
                    'update',
                    'parties',
                    $party->id,
                    $party->getOriginal(),
                    $changes,
                    "Updated party {$party->name}"
                );
            }
        });

        static::deleted(function ($party) {
            \App\Models\AuditLog::log(
                'delete',
                'parties',
                $party->id,
                $party->toArray(),
                null,
                "Deleted party {$party->name}"
            );
        });

        static::restored(function ($party) {
            \App\Models\AuditLog::log(
                'restore',
                'parties',
                $party->id,
                null,
                $party->toArray(),
                "Restored party {$party->name}"
            );
        });
    }

    /**
     * Get the company that owns the party.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function countryModel()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    public function stateModel()
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    public function cityModel()
    {
        return $this->belongsTo(City::class, 'city_id');
    }

    /**
     * Get the financial year for the party.
     */
    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    /**
     * Get the vouchers for the party.
     */
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    /**
     * Scope: Get parties by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Get active parties
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get debtors
     */
    public function scopeDebtors($query)
    {
        return $query->where('type', 'debtor');
    }

    /**
     * Scope: Get creditors
     */
    public function scopeCreditors($query)
    {
        return $query->where('type', 'creditor');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type === 'debtor' ? 'Debtor' : 'Creditor';
    }

    /**
     * Generate next party code.
     * Debtors (AR): AR001, AR002, ...
     * Creditors (AP): AP001, AP002, ...
     */
    public static function generateCode(string $type, int $companyId): string
    {
        $prefix = $type === 'debtor' ? 'AR' : 'AP';

        $lastParty = static::where('company_id', $companyId)
            ->where('party_code', 'like', "{$prefix}%")
            ->orderBy('party_code', 'desc')
            ->first();

        if ($lastParty) {
            $lastNumber = intval(substr($lastParty->party_code, strlen($prefix)));
            return $prefix . str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        }

        return $prefix . '001';
    }
}
