<?php

namespace App\Domains\Auth\Private\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $role = $this->route('role');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)],
            'slug' => ['required', 'string', 'max:255', Rule::unique('roles', 'slug')->ignore($role)],
            'description' => ['nullable', 'string', 'max:65535'],
        ];
    }
}
