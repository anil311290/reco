<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherLineResource extends JsonResource
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
            'account_id' => $this->account_id,
            'account' => $this->when(
                $this->relationLoaded('account') && $this->account !== null,
                fn () => new AccountResource($this->account)
            ),
            'party_id' => $this->party_id,
            'party' => $this->when(
                $this->relationLoaded('party') && $this->party !== null,
                fn () => new PartyResource($this->party)
            ),
            'debit' => $this->debit,
            'credit' => $this->credit,
            'description' => $this->description,
            'sort_order' => $this->sort_order ?? 0,
        ];
    }
}
