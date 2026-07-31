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
        $this->resource->loadMissing(['party', 'lines.account', 'lines.party', 'financialYear']);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'version' => (int) $this->version,
            'company_id' => $this->company_id,
            'financial_year_id' => $this->financial_year_id,
            'financial_year' => $this->when(
                $this->relationLoaded('financialYear') && $this->financialYear !== null,
                fn () => [
                    'id' => $this->financialYear->id,
                    'name' => $this->financialYear->name,
                ]
            ),
            'voucher_number' => $this->voucher_number,
            'voucher_type' => $this->voucher_type,
            'type_label' => $this->type_label,
            'voucher_date' => $this->voucher_date?->toISOString(),
            'party_id' => $this->party_id,
            'party' => $this->when(
                $this->relationLoaded('party') && $this->party !== null,
                fn () => new PartyResource($this->party)
            ),
            'narration' => $this->narration,
            'total_debit' => $this->total_debit,
            'total_credit' => $this->total_credit,
            'status' => $this->status,
            'is_balanced' => $this->isBalanced(),
            'lines' => VoucherLineResource::collection($this->lines),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
