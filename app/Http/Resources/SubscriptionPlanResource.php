<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'monthly_price' => (float) $this->monthly_price,
            'yearly_price' => (float) $this->yearly_price,
            'lifetime_price' => (float) $this->lifetime_price,
            'currency' => $this->currency,
            'trial_days' => $this->trial_days,
            'max_users' => $this->max_users,
            'max_transactions' => $this->max_transactions,
            'max_accounts' => $this->max_accounts,
            'max_parties' => $this->max_parties,
            'features' => $this->features,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'is_visible' => $this->is_visible,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
