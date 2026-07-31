<?php

namespace App\Http\Resources;

use App\Services\PartyService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $typeLocked = app(PartyService::class)->isTransactionallyUsed($this->id);

        return [
            'id' => $this->id,
            'party_code' => $this->party_code,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'mobile' => $this->mobile,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'gst_number' => $this->gst_number,
            'pan_number' => $this->pan_number,
            'opening_balance' => $this->opening_balance,
            'opening_balance_type' => $this->opening_balance_type,
            'opening_date' => $this->opening_date?->toISOString(),
            'remarks' => $this->remarks,
            'is_active' => $this->is_active,
            'type_locked' => $typeLocked,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
