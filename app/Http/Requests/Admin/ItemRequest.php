<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('item') ?? $this->route('id');

        return [
            'item_code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('items', 'item_code')->ignore($itemId)->where('company_id', $this->user()->company_id),
            ],
            'name' => 'required|string|max:255',
            'hsn_sac_code' => 'nullable|string|max:20',
            'type' => ['required', Rule::in(['goods', 'service'])],
            'category_id' => [
                'nullable',
                Rule::exists('item_categories', 'id')->where('company_id', $this->user()->company_id),
            ],
            'tax_rate_id' => 'nullable|exists:tax_rates,id',
            'income_account_id' => 'nullable|exists:accounts,id',
            'expense_account_id' => 'nullable|exists:accounts,id',
            'purchase_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:1000',
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('items', 'barcode')->ignore($itemId)->where('company_id', $this->user()->company_id),
            ],
            'opening_stock' => 'nullable|numeric|min:0',
            'is_stockable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'item_code.required' => 'Item code is required',
            'item_code.unique' => 'This item code already exists',
            'name.required' => 'Item name is required',
            'type.required' => 'Item type is required',
        ];
    }
}
