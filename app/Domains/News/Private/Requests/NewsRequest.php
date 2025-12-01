<?php

namespace App\Domains\News\Private\Requests;

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NewsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole([Roles::ADMIN, Roles::TECH_ADMIN]) ?? false;
    }

    public function rules(): array
    {
        $newsId = $this->route('news')?->id;
        $isUpdate = $newsId !== null;

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('news', 'slug')->ignore($newsId),
            ],
            'summary' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'header_image' => ['nullable', 'image', 'max:2048'], // 2MB max
            'header_image_remove' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'is_pinned' => ['boolean'],
            'meta_description' => ['nullable', 'string', 'max:160'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_pinned' => $this->boolean('is_pinned'),
            'header_image_remove' => $this->boolean('header_image_remove'),
        ]);
    }

    public function messages(): array
    {
        return [
            'title.required' => __('news::admin.validation.title_required'),
            'title.max' => __('news::admin.validation.title_max'),
            'slug.required' => __('news::admin.validation.slug_required'),
            'slug.regex' => __('news::admin.validation.slug_format'),
            'slug.unique' => __('news::admin.validation.slug_unique'),
            'summary.required' => __('news::admin.validation.summary_required'),
            'summary.max' => __('news::admin.validation.summary_max'),
            'content.required' => __('news::admin.validation.content_required'),
            'header_image.image' => __('news::admin.validation.header_image_type'),
            'header_image.max' => __('news::admin.validation.header_image_max'),
            'status.required' => __('news::admin.validation.status_required'),
            'status.in' => __('news::admin.validation.status_invalid'),
            'meta_description.max' => __('news::admin.validation.meta_description_max'),
        ];
    }
}
