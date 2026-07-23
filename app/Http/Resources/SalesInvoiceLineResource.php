<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceLineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lineType = $this->line_type ?? 'item';

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'sales_invoice_id' => $this->sales_invoice_id,
            'line_type' => $lineType,
            'kind' => $lineType === 'service' ? 'service' : 'item',
            'item_id' => $this->item_id,
            'item' => new ItemResource($this->whenLoaded('item')),
            'account_id' => $this->account_id,
            'account' => new AccountResource($this->whenLoaded('account')),
            'tax_rate_id' => $this->tax_rate_id,
            'tax_rate' => new TaxRateResource($this->whenLoaded('taxRate')),
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'amount' => $lineType === 'service'
                ? (float) $this->unit_price
                : (float) $this->quantity * (float) $this->unit_price,
            'discount_percentage' => (float) $this->discount_percentage,
            'discount_amount' => (float) $this->discount_amount,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'sort_order' => $this->sort_order,
        ];
    }
}
