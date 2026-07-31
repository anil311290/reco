<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'version' => (int) $this->version,
            'account_code' => $this->account_code,
            'account_name' => $this->account_name,
            'account_type' => $this->account_type,
            'type_label' => $this->type_label,
            'entry_source' => $this->entry_source,
            'is_system' => (bool) $this->is_system,
            'is_cash_bank_od' => (bool) $this->is_cash_bank_od,
            'opening_balance' => $this->opening_balance,
            'opening_date' => $this->opening_date?->toISOString(),
            'remarks' => $this->remarks,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
