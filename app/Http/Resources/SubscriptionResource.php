<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'company_id' => $this->company_id,
            'plan_id' => $this->plan_id,
            'plan' => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'status' => $this->status,
            'billing_cycle' => $this->billing_cycle,
            'start_date' => $this->start_date?->toISOString(),
            'trial_end_date' => $this->trial_end_date?->toISOString(),
            'current_period_start' => $this->current_period_start?->toISOString(),
            'current_period_end' => $this->current_period_end?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'amount' => (float) $this->amount,
            'currency' => $this->currency,
            'is_on_trial' => $this->isOnTrial(),
            'is_expired' => $this->isExpired(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
