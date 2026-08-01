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
        $isAdvanced = $this->input('mode') === 'advanced';

        $rules = [
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('static_pages', 'slug')->ignore($pageId),
            ],
            'summary' => ['nullable', 'string', 'max:500'],
            // Media image-field payload: a new upload (file) xor a reused/kept path.
            'header_image' => ['nullable', 'array'],
            'header_image.file' => ['nullable', 'image', 'max:2048'],
            'header_image.path' => ['nullable', 'string', 'max:1024'],
            'status' => ['required', Rule::in(['draft', 'published'])],
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
}
