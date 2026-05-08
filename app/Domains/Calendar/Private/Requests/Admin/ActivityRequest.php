<?php

namespace App\Domains\Calendar\Private\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:5120'],
            'image_remove' => ['nullable'],
            'role_restrictions' => ['nullable', 'array'],
            'role_restrictions.*' => ['nullable', 'string'],
            'requires_subscription' => ['nullable', 'boolean'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'preview_starts_at' => ['nullable', 'date'],
            'active_starts_at' => ['nullable', 'date'],
            'active_ends_at' => ['nullable', 'date'],
            'archived_at' => ['nullable', 'date'],
        ];

        if ($this->isMethod('POST')) {
            $rules['activity_type'] = ['required', 'string'];
        }

        return $rules;
    }
}
