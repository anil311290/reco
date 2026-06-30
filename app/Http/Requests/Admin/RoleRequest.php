<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends BaseFormRequest
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
        $roleId = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'slug')->ignore($roleId),
            ],
            'description' => 'nullable|string|max:500',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Role name is required',
            'slug.required' => 'Role slug is required',
            'slug.unique' => 'This slug is already taken',
            'slug.regex' => 'Slug can only contain lowercase letters, numbers, and hyphens',
            'permissions.*.exists' => 'One or more selected permissions are invalid',
        ];
    }
}
