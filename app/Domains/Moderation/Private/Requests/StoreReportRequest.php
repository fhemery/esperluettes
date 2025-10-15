<?php

namespace App\Domains\Moderation\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        // User must be authenticated and have verified email
        return $this->user() && $this->user()->hasVerifiedEmail();
    }

    public function rules(): array
    {
        return [
            'topic_key' => ['required', 'string', 'max:255'],
            'entity_id' => ['required', 'integer', 'min:1'],
            'reason_id' => ['required', 'integer', 'exists:moderation_reasons,id'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'topic_key.required' => __('moderation::report.validation.topic_required'),
            'entity_id.required' => __('moderation::report.validation.entity_required'),
            'reason_id.required' => __('moderation::report.validation.reason_required'),
            'reason_id.exists' => __('moderation::report.validation.reason_invalid'),
            'description.max' => __('moderation::report.validation.description_max'),
        ];
    }
}
