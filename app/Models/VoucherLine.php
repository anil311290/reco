<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherLine extends Model
{
    use FormatsHumanReadableDates, HasFactory, HasAuditFields, HasUuid, HasVersioning;

    protected $fillable = [
        'uuid',
        'voucher_id',
        'account_id',
        'party_id',
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

    /**
     * Get the party for the line (set on AR/AP control-account legs).
     */
    public function party()
    {
        return $this->belongsTo(Party::class);
    }
}
