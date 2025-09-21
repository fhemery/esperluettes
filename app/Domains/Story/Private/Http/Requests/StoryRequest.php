<?php

namespace App\Domains\Story\Private\Http\Requests;

use App\Domains\Story\Private\Models\Story;
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
            'description' => ['required', 'string', 'minstripped:100', 'maxstripped:1000'],
            'visibility' => ['required', 'in:' . implode(',', Story::visibilityOptions())],
            'story_ref_type_id' => ['required', 'integer', 'exists:story_ref_types,id'],
            'story_ref_audience_id' => ['required', 'integer', 'exists:story_ref_audiences,id'],
            'story_ref_copyright_id' => ['required', 'integer', 'exists:story_ref_copyrights,id'],
            'story_ref_status_id' => ['nullable', 'integer', 'exists:story_ref_statuses,id'],
            'story_ref_feedback_id' => ['nullable', 'integer', 'exists:story_ref_feedbacks,id'],
            'story_ref_genre_ids' => ['required', 'array', 'min:1', 'max:3'],
            'story_ref_genre_ids.*' => ['integer', 'exists:story_ref_genres,id'],
            // Trigger Warnings (0..N when listed)
            'story_ref_trigger_warning_ids' => ['nullable', 'array'],
            'story_ref_trigger_warning_ids.*' => ['integer', 'exists:story_ref_trigger_warnings,id'],
            // TW disclosure: listed | no_tw | unspoiled (required)
            'tw_disclosure' => ['required', 'in:' . implode(',', Story::twDisclosureOptions())],
        ];
    }

    protected function prepareForValidation(): void
    {
        // Trim title so min:1 applies to the trimmed value
        $title = $this->input('title');
        if (is_string($title)) {
            $title = trim($title);
        }

        // Purify description if provided; if empty after stripping tags, set to null
        $description = $this->input('description');
        if ($description !== null) {
            $description = Purifier::clean((string) $description, 'strict');
            $plain = trim(strip_tags($description));
            
        }

        // Normalize TWs based on tw_disclosure: if not 'listed', drop any provided TW IDs
        $twDisclosure = $this->input('tw_disclosure');
        $twIds = $this->input('story_ref_trigger_warning_ids');
        if ($twDisclosure !== Story::TW_LISTED) {
            $twIds = [];
        }

        $this->merge([
            'title' => $title,
            'description' => $description,
            'story_ref_trigger_warning_ids' => $twIds,
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

            'description.required' => __('story::validation.description.required'),
            'description.string' => __('story::validation.description.string'),
            'description.minstripped' => __('story::validation.description.min', ['min' => 100]),
            'description.maxstripped' => __('story::validation.description.max', ['max' => 1000]),

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

            'tw_disclosure.required' => __('story::validation.tw_disclosure.required'),
            'tw_disclosure.in' => __('story::validation.tw_disclosure.in'),
            'tw_disclosure.listed_requires_tw' => __('story::validation.tw_disclosure.listed_requires_tw'),
        ];
    }

    protected function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $twIds = $this->input('story_ref_trigger_warning_ids');
            $twIds = is_array($twIds) ? array_values(array_filter($twIds, fn($x) => $x !== null && $x !== '')) : [];
            $disclosure = $this->input('tw_disclosure');

            // When 'listed' is selected, at least one TW must be provided
            if ($disclosure === Story::TW_LISTED && empty($twIds)) {
                $v->errors()->add('tw_disclosure', __('story::validation.tw_disclosure.listed_requires_tw'));
            }
        });
    }
}
