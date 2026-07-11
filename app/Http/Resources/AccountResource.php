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
            'account_code' => $this->account_code,
            'account_name' => $this->account_name,
            'account_type' => $this->account_type,
            'type_label' => $this->type_label,
            'transaction_mode' => $this->transaction_mode,
            'transaction_mode_label' => $this->transaction_mode_label,
            'opening_balance' => $this->opening_balance,
            'opening_date' => $this->opening_date?->toISOString(),
            'remarks' => $this->remarks,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
