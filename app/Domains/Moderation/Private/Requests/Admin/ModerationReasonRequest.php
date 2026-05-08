<?php

namespace App\Domains\Moderation\Private\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ModerationReasonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'topic_key' => ['required', 'string', 'max:255'],
            'label' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
