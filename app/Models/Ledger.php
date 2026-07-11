<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ledger extends Model
{
    use FormatsHumanReadableDates, HasFactory, HasUuid, HasVersioning;

    protected static function booted(): void
    {
        static::created(function ($ledger) {
            \App\Models\AuditLog::log(
                'create',
                'ledger',
                $ledger->id,
                null,
                $ledger->toArray(),
                "Created ledger entry for account {$ledger->account_id}"
            );
        });

        static::updated(function ($ledger) {
            $changes = $ledger->getChanges();
            if (!empty($changes)) {
                \App\Models\AuditLog::log(
                    'update',
                    'ledger',
                    $ledger->id,
                    $ledger->getOriginal(),
                    $changes,
                    "Updated ledger entry {$ledger->id}"
                );
            }
        });

        static::deleted(function ($ledger) {
            \App\Models\AuditLog::log(
                'delete',
                'ledger',
                $ledger->id,
                $ledger->toArray(),
                null,
                "Deleted ledger entry {$ledger->id}"
            );
        });
    }

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'account_id',
        'voucher_id',
        'transaction_date',
        'reference_type',
        'reference_id',
        'description',
        'debit',
        'credit',
        'running_balance',
        'balance_type',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'running_balance' => 'decimal:2',
    ];

    /**
     * Get the company that owns the ledger entry.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the financial year for the ledger entry.
     */
    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    /**
     * Get the account for the ledger entry.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the voucher for the ledger entry.
     */
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Scope: Get entries for a specific account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    /**
     * Scope: Get entries within date range
     */
    public function scopeDateRange($query, string $from, string $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    /**
     * Scope: Get entries for a financial year
     */
    public function scopeForFinancialYear($query, int $financialYearId)
    {
        return $query->where('financial_year_id', $financialYearId);
    }
}
