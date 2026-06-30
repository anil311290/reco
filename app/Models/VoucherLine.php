<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherLine extends Model
{
    use HasFactory, HasAuditFields, HasUuid, HasVersioning;

    protected $fillable = [
        'uuid',
        'voucher_id',
        'account_id',
        'debit',
        'credit',
        'description',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    /**
     * Get the voucher that owns the line.
     */
    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Get the account for the line.
     */
    public function account()
    {
        return $this->belongsTo(Account::class);
    }
}
