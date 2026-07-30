<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class PartyRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $partyId = $this->route('party') ?? $this->route('id');

        return [
            'party_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                Rule::unique('parties', 'party_code')->ignore($partyId),
            ],
            'name' => 'required|string|max:255',
            'type' => ['required', Rule::in(['debtor', 'creditor'])],
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:500',
            'state_id' => 'required|exists:states,id',
            'city_id' => 'required|exists:cities,id',
            'postal_code' => 'required|string|max:20',
            'gstin' => 'nullable|string|max:20',
            'pan_number' => 'nullable|string|max:20',
            'opening_balance' => 'nullable|numeric|min:0',
            'opening_balance_type' => ['required', Rule::in(['debit', 'credit'])],
            'opening_date' => 'nullable|date',
            'remarks' => 'nullable|string|max:500',
            'is_active' => 'boolean',
            'duplicate_action' => ['nullable', Rule::in(['restore', 'new_entry'])],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Party name is required',
            'type.required' => 'Party type is required',
            'type.in' => 'Invalid party type',
            'party_code.unique' => 'This party code is already taken',
            'state_id.required' => 'State is required',
            'state_id.exists' => 'Invalid state selected',
            'city_id.required' => 'City is required',
            'city_id.exists' => 'Invalid city selected',
            'postal_code.required' => 'Postal code is required',
            'opening_balance_type.required' => 'Opening balance type is required',
            'opening_balance_type.in' => 'Invalid opening balance type',
        ];
    }
}
