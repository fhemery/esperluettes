<?php

namespace App\Domains\Auth\Private\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ActivationCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sponsor_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'comment' => ['nullable', 'string', 'max:500'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
