<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Route middleware handles auth/verified/role; allow here.
        return true;
    }

    public function rules(): array
    {
        return [
            'story_id' => ['required', 'integer', 'min:1'],
            'target_word_count' => ['required', 'integer', 'min:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'story_id.required' => __('jardino::validation.story_id.required'),
            'story_id.integer' => __('jardino::validation.story_id.integer'),
            'story_id.min' => __('jardino::validation.story_id.min'),
            'target_word_count.required' => __('jardino::validation.target_word_count.required'),
            'target_word_count.integer' => __('jardino::validation.target_word_count.integer'),
            'target_word_count.min' => __('jardino::validation.target_word_count.min'),
        ];
    }
}
