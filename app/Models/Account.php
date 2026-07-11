<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class Account extends Model
{
    use FormatsHumanReadableDates, HasFactory, HasAuditFields, HasUuid, HasVersioning, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'account_code',
        'account_name',
        'account_type',
        'entry_source',
        'transaction_mode',
        'opening_balance',
        'balance_type',
        'opening_date',
        'remarks',
        'is_active',
        'is_system',
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
        'is_system' => 'boolean',
    ];

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::created(function ($account) {
            \App\Models\AuditLog::log(
                'create',
                'Account',
                $account->id,
                null,
                $account->toArray()
            );
        });

        static::updated(function ($account) {
            $changes = $account->getChanges();
            if (!empty($changes)) {
                \App\Models\AuditLog::log(
                    'update',
                    'Account',
                    $account->id,
                    $account->getOriginal(),
                    $changes
                );
            }
        });

        static::deleted(function ($account) {
            \App\Models\AuditLog::log(
                'delete',
                'Account',
                $account->id,
                $account->toArray(),
                null
            );
        });
    }

    /**
     * Get the company that owns the account.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the financial year for the account.
     */
    public function financialYear()
    {
        return $this->belongsTo(FinancialYear::class);
    }

    /**
     * Scope: Get accounts by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    /**
     * Scope: Get active accounts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Get accounts by company
     */
    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Get account type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->account_type) {
            'asset' => 'Asset',
            'liability' => 'Liability',
            'income' => 'Income',
            'expense' => 'Expense',
            'equity' => 'Equity',
            default => ucfirst($this->account_type),
        };
    }

    /**
     * Get transaction mode label.
     */
    public function getTransactionModeLabelAttribute(): string
    {
        return match($this->transaction_mode) {
            'cash' => 'Cash',
            'bank' => 'Bank',
            'od' => 'OD',
            default => '-',
        };
    }

    /**
     * Check if account has children (no parent-child hierarchy in current schema)
     */
    public function hasChildren(): bool
    {
        return false;
    }

    /**
     * Get full account path
     */
    public function getFullPathAttribute(): string
    {
        return $this->account_name;
    }

    /**
     * Account code ranges:
     *   1000         → Opening Balance Difference (system suspense)
     *   1001–1249    → Assets  (user)
     *   1250         → Accounts Receivable (system, reserved)
     *   1251–1499    → Liabilities (user)
     *   1500         → Accounts Payable (system, reserved)
     *   1501         → Sales Revenue / AR Income (system, reserved)
     *   1502–1750    → Income (user)
     *   1751         → Purchases / AP Expense (system, reserved)
     *   1752–2000    → Expenses (user)
     *   2001–2500    → Equity (user)
     */
    public const CODE_RANGES = [
        'asset'     => ['start' => 1001, 'end' => 1249],
        'liability' => ['start' => 1251, 'end' => 1499],
        'income'    => ['start' => 1502, 'end' => 1750],
        'expense'   => ['start' => 1752, 'end' => 2000],
        'equity'    => ['start' => 2001, 'end' => 2500],
    ];

    /** Codes reserved for system accounts — cannot be assigned to user accounts */
    public const RESERVED_CODES = [
        '1000' => 'Opening Balance Difference',
        '1250' => 'Accounts Receivable',
        '1500' => 'Accounts Payable',
        '1501' => 'Sales Revenue (AR)',
        '1751' => 'Purchases (AP)',
    ];

    /** Fixed system account codes */
    public const CODE_SUSPENSE = '1000';
    public const CODE_AR       = '1250';
    public const CODE_AP       = '1500';
    public const CODE_AR_INCOME  = '1501';
    public const CODE_AP_EXPENSE = '1751';

    /**
     * Generate the next available account code for the given type and company.
     */
    public static function generateCode(string $type, int $companyId): string
    {
        $range = self::CODE_RANGES[$type] ?? ['start' => 2501, 'end' => 9999];

        $query = static::withTrashed()
            ->where('company_id', $companyId)
            ->whereRaw("CAST(account_code AS UNSIGNED) BETWEEN ? AND ?", [$range['start'], $range['end']]);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            $query->whereRaw("account_code REGEXP '^[0-9]+$'");
        } else {
            $query->whereRaw("account_code GLOB '[0-9]*'");
        }

        $last = $query->orderByRaw('CAST(account_code AS UNSIGNED) DESC')
            ->value('account_code');

        $next = $last ? ((int) $last + 1) : $range['start'];

        // Skip any reserved codes that fall inside the range
        $reserved = array_keys(self::RESERVED_CODES);
        while (in_array((string) $next, $reserved) && $next <= $range['end']) {
            $next++;
        }

        if ($next > $range['end']) {
            throw new \RuntimeException(
                "Account code range exhausted for type '{$type}'. Contact your administrator."
            );
        }

        return (string) $next;
    }

    /**
     * Check whether a code is reserved for a system account.
     */
    public static function isReservedCode(string $code): bool
    {
        return array_key_exists($code, self::RESERVED_CODES);
    }
}
