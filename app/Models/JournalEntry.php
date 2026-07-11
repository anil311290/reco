<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JournalEntry extends Model
{
    use FormatsHumanReadableDates, HasFactory, HasAuditFields, HasUuid, HasVersioning, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'entry_date',
        'module',
        'source_type',
        'source_id',
        'voucher_id',
        'ledger_id',
        'account_id',
        'party_id',
        'debit',
        'credit',
        'amount_signed',
        'head_name_snapshot',
        'narration',
        'line_no',
        'status',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'deleted_by',
        'deleted_by_id',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'amount_signed' => 'decimal:2',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }
}
