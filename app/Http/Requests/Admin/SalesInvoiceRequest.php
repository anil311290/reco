<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;

class SalesInvoiceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'party_id' => 'required|exists:parties,id',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:invoice_date',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'nullable|exists:items,id',
            'lines.*.account_id' => 'nullable|exists:accounts,id',
            'lines.*.tax_rate_id' => 'nullable|exists:tax_rates,id',
            'lines.*.description' => 'nullable|string|max:500',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'party_id.required' => 'Please select a customer',
            'party_id.exists' => 'Selected customer does not exist',
            'invoice_date.required' => 'Invoice date is required',
            'due_date.required' => 'Due date is required',
            'due_date.after_or_equal' => 'Due date must be on or after invoice date',
            'lines.required' => 'At least one line item is required',
            'lines.min' => 'At least one line item is required',
            'lines.*.quantity.required' => 'Quantity is required for each line',
            'lines.*.unit_price.required' => 'Unit price is required for each line',
        ];
    }
}
