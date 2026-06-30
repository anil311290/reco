<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    protected $fillable = ['state_id', 'country_id', 'name', 'is_active', 'sort_order'];

    protected $casts = ['is_active' => 'boolean'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function scopeForState($query, int $stateId)
    {
        return $query->where('state_id', $stateId);
    }
}
