<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'purchase_invoice_id' => $this->purchase_invoice_id,
            'item_id' => $this->item_id,
            'item' => new ItemResource($this->whenLoaded('item')),
            'account_id' => $this->account_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'tax_rate_id' => $this->tax_rate_id,
            'tax_rate' => new TaxRateResource($this->whenLoaded('taxRate')),
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount_percentage' => (float) $this->discount_percentage,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'sort_order' => $this->sort_order,
        ];
    }
}
