<?php

declare(strict_types=1);

namespace App\Domains\Message\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ComposeMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Only admins can compose messages in v1
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:1000'],
            'target_users' => ['nullable', 'array'],
            'target_users.*' => ['integer', 'exists:users,id'],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['string', 'exists:roles,slug'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'target_users' => $this->input('target_users', []),
            'target_roles' => $this->input('target_roles', []),
        ]);
    }
}
