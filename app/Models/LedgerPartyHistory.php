<?php

namespace App\Models;

use App\Traits\HasUuid;
use App\Traits\HasVersioning;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LedgerPartyHistory extends Model
{
    use HasFactory, HasUuid, HasVersioning;

    protected $fillable = [
        'uuid',
        'company_id',
        'financial_year_id',
        'ledger_id',
        'party_id',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
        'created_by_ip',
        'version',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function party()
    {
        return $this->belongsTo(Party::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
