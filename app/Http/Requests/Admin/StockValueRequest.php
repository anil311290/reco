<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StockValueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $entryId = $this->route('entry');

        return [
            'financial_year_id' => [
                'required',
                'integer',
                Rule::exists('financial_years', 'id')->where('company_id', $this->user()?->company_id),
            ],
            'stock_value' => ['required', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'valuation_date' => [
                'required',
                'date',
                Rule::unique('stock_value_entries', 'valuation_date')
                    ->where('company_id', $this->user()?->company_id)
                    ->where('financial_year_id', $this->input('financial_year_id'))
                    ->ignore($entryId),
            ],
        ];
    }
}
