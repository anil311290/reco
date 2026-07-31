<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use FormatsHumanReadableDates, HasFactory, HasAuditFields, HasUuid, HasVersioning, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'category_id',
        'item_code',
        'name',
        'hsn_sac_code',
        'type',
        'tax_rate_id',
        'income_account_id',
        'expense_account_id',
        'purchase_price',
        'selling_price',
        'unit',
        'description',
        'barcode',
        'opening_stock',
        'current_stock',
        'reorder_level',
        'is_active',
        'is_stockable',
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
        'purchase_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'opening_stock' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'reorder_level' => 'decimal:2',
        'is_active' => 'boolean',
        'is_stockable' => 'boolean',
    ];

    /**
     * Set defaults for new items
     */
    protected static function booted(): void
    {
        static::creating(function ($item) {
            $item->item_code = self::generateCode((int) $item->company_id);

            if (empty($item->type)) {
                $item->type = 'goods';
            }

            if ($item->type === 'service') {
                $item->is_stockable = false;
                if ($item->opening_stock === null) {
                    $item->opening_stock = 0;
                }
                if ($item->current_stock === null) {
                    $item->current_stock = 0;
                }
            }

            // Default income ledger by item type.
            if (empty($item->income_account_id) && $item->company_id) {
                $incomeCode = $item->type === 'service'
                    ? Account::CODE_SERVICE_INCOME
                    : Account::CODE_AR_INCOME;

                $incomeAccount = Account::where('company_id', $item->company_id)
                    ->where('account_type', 'income')
                    ->where('account_code', $incomeCode)
                    ->first();

                if ($incomeAccount) {
                    $item->income_account_id = $incomeAccount->id;
                }
            }

            // Default Purchases expense account for goods only
            if ($item->type === 'goods' && empty($item->expense_account_id) && $item->company_id) {
                $purchaseAccount = Account::where('company_id', $item->company_id)
                    ->where('account_type', 'expense')
                    ->where('is_system', true)
                    ->first();

                if (!$purchaseAccount) {
                    $purchaseAccount = Account::where('company_id', $item->company_id)
                        ->where('account_type', 'expense')
                        ->where('account_name', 'like', '%purchase%')
                        ->first();
                }

                if ($purchaseAccount) {
                    $item->expense_account_id = $purchaseAccount->id;
                }
            }
        });
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'category_id');
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class);
    }

    public function incomeAccount()
    {
        return $this->belongsTo(Account::class, 'income_account_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(Account::class, 'expense_account_id');
    }

    /**
     * Update stock quantity.
     */
    public function updateStock(float $quantity, string $type = 'add'): void
    {
        if ($type === 'add') {
            $this->increment('current_stock', $quantity);
        } else {
            $this->decrement('current_stock', $quantity);
        }
    }

    public function isLowStock(): bool
    {
        if ($this->type !== 'goods' || $this->is_stockable === false) {
            return false;
        }

        return (float) $this->current_stock <= (float) ($this->reorder_level ?? 0);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLowStock($query)
    {
        return $query->where('type', 'goods')
            ->where('is_stockable', true)
            ->whereColumn('current_stock', '<=', 'reorder_level');
    }

    /**
     * Generate the next item code for a company.
     */
    public static function generateCode(int $companyId): string
    {
        $lastNumber = static::withTrashed()
            ->where('company_id', $companyId)
            ->where('item_code', 'like', 'ITEM-%')
            ->pluck('item_code')
            ->reduce(function (int $highest, string $code): int {
                if (!preg_match('/^ITEM-(\d+)$/', $code, $matches)) {
                    return $highest;
                }

                return max($highest, (int) $matches[1]);
            }, 0);

        return 'ITEM-' . str_pad((string) ($lastNumber + 1), 3, '0', STR_PAD_LEFT);
    }
}
