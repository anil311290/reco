<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoucherResource extends JsonResource
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
            'voucher_number' => $this->voucher_number,
            'voucher_type' => $this->voucher_type,
            'type_label' => $this->type_label,
            'voucher_date' => $this->voucher_date?->toISOString(),
            'party_id' => $this->party_id,
            'party' => new PartyResource($this->whenLoaded('party')),
            'narration' => $this->narration,
            'total_debit' => $this->total_debit,
            'total_credit' => $this->total_credit,
            'status' => $this->status,
            'is_balanced' => $this->isBalanced(),
            'remarks' => $this->remarks,
            'lines' => VoucherLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
