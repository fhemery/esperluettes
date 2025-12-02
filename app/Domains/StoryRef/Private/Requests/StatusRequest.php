<?php

namespace App\Domains\StoryRef\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'tech-admin']) ?? false;
    }

    public function rules(): array
    {
        $statusId = $this->route('status')?->id;
        $isUpdate = $statusId !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('story_ref_statuses', 'slug')->ignore($statusId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'order' => $isUpdate ? ['sometimes', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => __('story_ref::admin.statuses.validation.name_required'),
            'name.max' => __('story_ref::admin.statuses.validation.name_max'),
            'slug.required' => __('story_ref::admin.statuses.validation.slug_required'),
            'slug.regex' => __('story_ref::admin.statuses.validation.slug_format'),
            'slug.unique' => __('story_ref::admin.statuses.validation.slug_unique'),
            'order.required' => __('story_ref::admin.statuses.validation.order_required'),
            'order.integer' => __('story_ref::admin.statuses.validation.order_integer'),
            'description.max' => __('story_ref::admin.statuses.validation.description_max'),
        ];
    }
}
