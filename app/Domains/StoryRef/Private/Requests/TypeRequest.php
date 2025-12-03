<?php

namespace App\Domains\StoryRef\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'tech-admin']) ?? false;
    }

    public function rules(): array
    {
        $typeId = $this->route('type')?->id;
        $isUpdate = $typeId !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('story_ref_types', 'slug')->ignore($typeId),
            ],
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
            'name.required' => __('story_ref::admin.types.validation.name_required'),
            'name.max' => __('story_ref::admin.types.validation.name_max'),
            'slug.required' => __('story_ref::admin.types.validation.slug_required'),
            'slug.regex' => __('story_ref::admin.types.validation.slug_format'),
            'slug.unique' => __('story_ref::admin.types.validation.slug_unique'),
            'order.required' => __('story_ref::admin.types.validation.order_required'),
            'order.integer' => __('story_ref::admin.types.validation.order_integer'),
        ];
    }
}
