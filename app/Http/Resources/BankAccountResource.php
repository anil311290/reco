<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'account_id' => $this->account_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'bank_name' => $this->bank_name,
            'branch_name' => $this->branch_name,
            'account_number' => $this->account_number,
            'ifsc_code' => $this->ifsc_code,
            'account_holder_name' => $this->account_holder_name,
            'account_type' => $this->account_type,
            'opening_balance' => (float) $this->opening_balance,
            'opening_date' => $this->opening_date?->toISOString(),
            'upi_id' => $this->upi_id,
            'is_default' => $this->is_default,
            'is_active' => $this->is_active,
            'remarks' => $this->remarks,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
