<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class TaxRateRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $taxRateId = $this->route('tax_rate') ?? $this->route('id');

        return [
            'tax_name' => 'required|string|max:255',
            'tax_code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('tax_rates', 'tax_code')->ignore($taxRateId)->where('company_id', $this->user()->company_id),
            ],
            'tax_rate' => 'required|numeric|min:0|max:100',
            'tax_type' => ['required', Rule::in(['addition', 'deduction'])],
            'tax_category' => ['required', Rule::in(['GST', 'CGST', 'SGST', 'IGST', 'TDS', 'TCS', 'CESS', 'OTHER'])],
            'notes' => 'nullable|string',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_name.required' => 'Tax name is required',
            'tax_rate.required' => 'Tax rate percentage is required',
            'tax_type.required' => 'Tax type is required',
            'tax_category.required' => 'Tax category is required',
        ];
    }
}
