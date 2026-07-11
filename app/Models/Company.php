<?php

namespace App\Models;

use App\Models\Concerns\FormatsHumanReadableDates;

use App\Traits\HasAuditFields;
use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends Model
{
    use FormatsHumanReadableDates, HasFactory, HasAuditFields, HasUuid, SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'country_id',
        'state_id',
        'city_id',
        'postal_code',
        'gst_number',
        'pan_number',
        'logo',
        'favicon',
        'currency',
        'timezone',
        'financial_year_start',
        'financial_year_end',
        'is_active',
        'created_by',
        'updated_by',
        'created_by_ip',
        'updated_by_ip',
        'deleted_by',
        'deleted_by_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the users for the company.
     */
    public function users()
    {
        return $this->hasMany(User::class);
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

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function financialYears()
    {
        return $this->hasMany(FinancialYear::class);
    }

    public function accounts()
    {
        return $this->hasMany(Account::class);
    }

    public function parties()
    {
        return $this->hasMany(Party::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)->whereIn('status', ['trial', 'active']);
    }

    public function theme()
    {
        return $this->hasOne(Theme::class)->where('is_active', true);
    }

    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    /**
     * Get current active financial year.
     */
    public function currentFinancialYear()
    {
        return $this->hasOne(FinancialYear::class)->where('is_current', true);
    }
}
