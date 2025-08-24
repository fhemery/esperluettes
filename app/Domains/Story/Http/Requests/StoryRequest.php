<?php

namespace App\Domains\Story\Http\Requests;

use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Http\FormRequest;

class StoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // must be authenticated
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:1', 'max:255'],
            'description' => ['nullable', 'string', 'max:3000'],
            'visibility' => ['required', 'in:' . implode(',', Story::visibilityOptions())],
            'story_ref_type_id' => ['required', 'integer', 'exists:story_ref_types,id'],
            'story_ref_audience_id' => ['required', 'integer', 'exists:story_ref_audiences,id'],
            'story_ref_copyright_id' => ['required', 'integer', 'exists:story_ref_copyrights,id'],
            'story_ref_genre_ids' => ['required', 'array', 'min:1', 'max:3'],
            'story_ref_genre_ids.*' => ['integer', 'exists:story_ref_genres,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Trim title so min:1 applies to the trimmed value
        $title = $this->input('title');
        if (is_string($title)) {
            $title = trim($title);
        }

        $this->merge([
            'title' => $title,
        ]);
    }

    public function messages(): array
    {
        // Return raw translation keys so tests (APP_LOCALE=zz) can assert them
        return [
            'title.required' => __('story::validation.title.required'),
            'title.string' => __('story::validation.title.string'),
            'title.min' => __('story::validation.title.min'),
            'title.max' => __('story::validation.title.max'),

            'description.string' => __('story::validation.description.string'),
            'description.max' => __('story::validation.description.max'),

            'visibility.required' => __('story::validation.visibility.required'),
            'visibility.in' => __('story::validation.visibility.in'),

            'story_ref_type_id.required' => __('story::validation.type.required'),
            'story_ref_type_id.integer' => __('story::validation.type.integer'),
            'story_ref_type_id.exists' => __('story::validation.type.exists'),

            'story_ref_audience_id.required' => __('story::validation.audience.required'),
            'story_ref_audience_id.integer' => __('story::validation.audience.integer'),
            'story_ref_audience_id.exists' => __('story::validation.audience.exists'),

            'story_ref_copyright_id.required' => __('story::validation.copyright.required'),
            'story_ref_copyright_id.integer' => __('story::validation.copyright.integer'),
            'story_ref_copyright_id.exists' => __('story::validation.copyright.exists'),

            'story_ref_genre_ids.required' => __('story::validation.genres.required'),
            'story_ref_genre_ids.array' => __('story::validation.genres.array'),
            'story_ref_genre_ids.min' => __('story::validation.genres.min'),
            'story_ref_genre_ids.max' => __('story::validation.genres.max'),
            'story_ref_genre_ids.*.integer' => __('story::validation.genres.integer'),
            'story_ref_genre_ids.*.exists' => __('story::validation.genres.exists'),
        ];
    }
}
