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
        ];
    }
}
