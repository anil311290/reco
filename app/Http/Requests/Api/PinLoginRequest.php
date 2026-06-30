<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PinLoginRequest extends FormRequest
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
        return [
            'pin' => 'required|string|min:4|max:6|regex:/^[0-9]+$/',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'pin.required' => 'PIN is required',
            'pin.min' => 'PIN must be at least 4 digits',
            'pin.max' => 'PIN must not exceed 6 digits',
            'pin.regex' => 'PIN must contain only numbers',
        ];
    }
}
