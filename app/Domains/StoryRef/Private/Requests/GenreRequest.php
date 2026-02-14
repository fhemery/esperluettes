<?php

namespace App\Domains\StoryRef\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'tech-admin']) ?? false;
    }

    public function rules(): array
    {
        $genreId = $this->route('genre')?->id;
        $isUpdate = $genreId !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('story_ref_genres', 'slug')->ignore($genreId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'order' => $isUpdate ? ['sometimes', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'has_cover' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'has_cover' => $this->boolean('has_cover'),
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => __('story_ref::admin.genres.validation.name_required'),
            'name.max' => __('story_ref::admin.genres.validation.name_max'),
            'slug.required' => __('story_ref::admin.genres.validation.slug_required'),
            'slug.regex' => __('story_ref::admin.genres.validation.slug_format'),
            'slug.unique' => __('story_ref::admin.genres.validation.slug_unique'),
            'order.required' => __('story_ref::admin.genres.validation.order_required'),
            'order.integer' => __('story_ref::admin.genres.validation.order_integer'),
            'description.max' => __('story_ref::admin.genres.validation.description_max'),
        ];
    }
}
