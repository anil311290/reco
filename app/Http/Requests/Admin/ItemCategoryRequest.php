<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class ItemCategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('item_category') ?? $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('item_categories', 'name')->ignore($categoryId)->where('company_id', $this->user()->company_id),
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Category name is required',
            'name.unique' => 'This category name already exists',
        ];
    }
}
