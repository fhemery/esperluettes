<?php

namespace App\Domains\Story\Http\Requests;

use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

class StoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // must be authenticated
    }

    public function rules(): array
    {
        return [
            'title' => ['required_trimmed', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'maxstripped:1000'],
            'visibility' => ['required', 'in:' . implode(',', Story::visibilityOptions())],
            'story_ref_type_id' => ['required', 'integer', 'exists:story_ref_types,id'],
            'story_ref_audience_id' => ['required', 'integer', 'exists:story_ref_audiences,id'],
            'story_ref_copyright_id' => ['required', 'integer', 'exists:story_ref_copyrights,id'],
            'story_ref_status_id' => ['nullable', 'integer', 'exists:story_ref_statuses,id'],
            'story_ref_feedback_id' => ['nullable', 'integer', 'exists:story_ref_feedbacks,id'],
            'story_ref_genre_ids' => ['required', 'array', 'min:1', 'max:3'],
            'story_ref_genre_ids.*' => ['integer', 'exists:story_ref_genres,id'],
            // Optional Trigger Warnings (0..N)
            'story_ref_trigger_warning_ids' => ['nullable', 'array'],
            'story_ref_trigger_warning_ids.*' => ['integer', 'exists:story_ref_trigger_warnings,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Trim title so min:1 applies to the trimmed value
        $title = $this->input('title');
        if (is_string($title)) {
            $title = trim($title);
        }

        // Purify description if provided
        $description = $this->input('description');
        if ($description !== null) {
            $description = Purifier::clean((string) $description, 'strict');
        }

        $this->merge([
            'title' => $title,
            'description' => $description,
        ]);
    }

    public function messages(): array
    {
        // Return raw translation keys so tests (APP_LOCALE=zz) can assert them
        return [
            'title.required_trimmed' => __('story::validation.title.required'),
            'title.required' => __('story::validation.title.required'),
            'title.string' => __('story::validation.title.string'),
            'title.min' => __('story::validation.title.min'),
            'title.max' => __('story::validation.title.max'),

            'description.string' => __('story::validation.description.string'),
            'description.maxstripped' => __('story::validation.description.max'),

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

            'story_ref_status_id.integer' => __('story::validation.status.integer'),
            'story_ref_status_id.exists' => __('story::validation.status.exists'),

            'story_ref_feedback_id.integer' => __('story::validation.feedback.integer'),
            'story_ref_feedback_id.exists' => __('story::validation.feedback.exists'),

            'story_ref_genre_ids.required' => __('story::validation.genres.required'),
            'story_ref_genre_ids.array' => __('story::validation.genres.array'),
            'story_ref_genre_ids.min' => __('story::validation.genres.min'),
            'story_ref_genre_ids.max' => __('story::validation.genres.max'),
            'story_ref_genre_ids.*.integer' => __('story::validation.genres.integer'),
            'story_ref_genre_ids.*.exists' => __('story::validation.genres.exists'),

            'story_ref_trigger_warning_ids.array' => __('story::validation.trigger_warnings.array'),
            'story_ref_trigger_warning_ids.*.integer' => __('story::validation.trigger_warnings.integer'),
            'story_ref_trigger_warning_ids.*.exists' => __('story::validation.trigger_warnings.exists'),
        ];
    }
}
