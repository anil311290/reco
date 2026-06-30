<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesInvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'invoice_number' => $this->invoice_number,
            'invoice_type' => $this->invoice_type ?? 'item',
            'invoice_date' => $this->invoice_date?->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
            'reference_number' => $this->reference_number,
            'party_id' => $this->party_id,
            'party' => new PartyResource($this->whenLoaded('party')),
            'financial_year_id' => $this->financial_year_id,
            'notes' => $this->notes,
            'payment_terms' => $this->payment_terms,
            'delivery_terms' => $this->delivery_terms,
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
            'is_recurring' => $this->is_recurring,
            'is_overdue' => $this->isOverdue(),
            'lines' => SalesInvoiceLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
