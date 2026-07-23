<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'kind' => 'item',
            'item_code' => $this->item_code,
            'name' => $this->name,
            'hsn_sac_code' => $this->hsn_sac_code,
            'type' => $this->type,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'tax_rate_id' => $this->tax_rate_id,
            'tax_rate' => new TaxRateResource($this->whenLoaded('taxRate')),
            'income_account_id' => $this->income_account_id,
            'expense_account_id' => $this->expense_account_id,
            'purchase_price' => (float) $this->purchase_price,
            'selling_price' => (float) $this->selling_price,
            'unit' => $this->unit,
            'description' => $this->description,
            'barcode' => $this->barcode,
            'opening_stock' => (float) $this->opening_stock,
            'current_stock' => (float) $this->current_stock,
            'reorder_level' => (float) $this->reorder_level,
            'is_active' => $this->is_active,
            'is_stockable' => $this->is_stockable,
            'is_low_stock' => $this->isLowStock(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
