<?php

namespace App\Domains\StaticPage\Private\Requests;

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StaticPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole([Roles::ADMIN, Roles::TECH_ADMIN]) ?? false;
    }

    public function rules(): array
    {
        $pageId = $this->route('staticPage')?->id;

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('static_pages', 'slug')->ignore($pageId),
            ],
            'summary' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'header_image' => ['nullable', 'image', 'max:2048'],
            'header_image_remove' => ['nullable'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('static::admin.validation.title_required'),
            'slug.required' => __('static::admin.validation.slug_required'),
            'slug.regex' => __('static::admin.validation.slug_format'),
            'slug.unique' => __('static::admin.validation.slug_unique'),
            'content.required' => __('static::admin.validation.content_required'),
        ];
    }

    protected function prepareForValidation(): void
    {
        // Convert checkbox/hidden boolean values
        if ($this->has('header_image_remove')) {
            $this->merge([
                'header_image_remove' => filter_var($this->header_image_remove, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }
}
