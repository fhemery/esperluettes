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
        $isAdvanced = $this->input('mode') === 'advanced';

        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('news', 'slug')->ignore($newsId),
            ],
            'summary' => ['required', 'string', 'max:500'],
            // Header image via the Media image-field: a new upload (file) xor a
            // reused/kept path; empty means the header was removed.
            'header_image' => ['nullable', 'array'],
            'header_image.file' => ['nullable', 'image', 'max:2048'], // 2MB max
            'header_image.path' => ['nullable', 'string', 'max:1024'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'is_pinned' => ['boolean'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'mode' => ['nullable', Rule::in(['simple', 'advanced'])],
        ];

        if ($isAdvanced) {
            // Advanced (MultiEdit): content is a generated cache, blocks are the source.
            $rules['content'] = ['nullable', 'string'];
            $rules['blocks_order'] = ['nullable', 'string'];
            $rules['blocks'] = ['required', 'array', 'min:1'];
            $rules['blocks.*.type'] = ['required', Rule::in(['text', 'image'])];
            $rules['blocks.*.html'] = ['nullable', 'string'];
            $rules['blocks.*.path'] = ['nullable', 'string', 'max:1024'];
            $rules['blocks.*.alt'] = ['nullable', 'string', 'max:255'];
            $rules['blocks.*.caption'] = ['nullable', 'string', 'max:255'];
            $rules['blocks.*.keep_original'] = ['nullable'];
            $rules['blocks.*.file'] = ['nullable', 'image', 'max:2048'];
        } else {
            $rules['content'] = ['required', 'string'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_pinned' => $this->boolean('is_pinned'),
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
            'header_image.file.image' => __('news::admin.validation.header_image_type'),
            'header_image.file.max' => __('news::admin.validation.header_image_max'),
            'status.required' => __('news::admin.validation.status_required'),
            'status.in' => __('news::admin.validation.status_invalid'),
            'meta_description.max' => __('news::admin.validation.meta_description_max'),
        ];
    }
}
