<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'tax_code' => $this->tax_code,
            'tax_name' => $this->tax_name,
            'tax_rate' => (float) $this->tax_rate,
            'tax_type' => $this->tax_type,
            'tax_category' => $this->tax_category,
            'notes' => $this->notes,
            'status' => $this->status,
            'name' => $this->tax_name,
            'code' => $this->tax_code,
            'rate' => (float) $this->tax_rate,
            'calculation_type' => $this->tax_type,
            'category' => $this->tax_category,
            'is_active' => $this->status === 'active',
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
