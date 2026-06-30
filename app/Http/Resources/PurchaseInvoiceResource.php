<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'invoice_number' => $this->invoice_number,
            'supplier_invoice_number' => $this->supplier_invoice_number,
            'invoice_date' => $this->invoice_date?->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
            'party_id' => $this->party_id,
            'party' => new PartyResource($this->whenLoaded('party')),
            'financial_year_id' => $this->financial_year_id,
            'notes' => $this->notes,
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_amount,
            'discount_percentage' => (float) $this->discount_percentage,
            'tax_amount' => (float) $this->tax_amount,
            'total' => (float) $this->total,
            'amount_paid' => (float) $this->amount_paid,
            'balance_due' => (float) $this->balance_due,
            'currency' => $this->currency,
            'status' => $this->status,
            'status_label' => ucfirst(str_replace('_', ' ', $this->status)),
            'is_overdue' => $this->isOverdue(),
            'lines' => PurchaseInvoiceLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
