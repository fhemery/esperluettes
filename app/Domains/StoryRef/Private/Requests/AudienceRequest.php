<?php

namespace App\Domains\StoryRef\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AudienceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(['admin', 'tech-admin']) ?? false;
    }

    public function rules(): array
    {
        $audienceId = $this->route('audience')?->id;
        $isUpdate = $audienceId !== null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('story_ref_audiences', 'slug')->ignore($audienceId),
            ],
            // Order is only required on create; on update it's managed via reorder
            'order' => $isUpdate ? ['sometimes', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_mature_audience' => ['boolean'],
            'threshold_age' => ['nullable', 'integer', 'min:1', 'max:99'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'is_mature_audience' => $this->boolean('is_mature_audience'),
        ]);
    }

    protected function withValidator($validator): void
    {
        $validator->after(function ($v) {
            $isMature = $this->boolean('is_mature_audience');
            $thresholdAge = $this->input('threshold_age');

            // If is_mature_audience is true, threshold_age must be set
            if ($isMature && ($thresholdAge === null || $thresholdAge === '')) {
                $v->errors()->add('threshold_age', __('story_ref::admin.audiences.validation.threshold_required_when_mature'));
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => __('story_ref::admin.audiences.validation.name_required'),
            'name.max' => __('story_ref::admin.audiences.validation.name_max'),
            'slug.required' => __('story_ref::admin.audiences.validation.slug_required'),
            'slug.regex' => __('story_ref::admin.audiences.validation.slug_format'),
            'slug.unique' => __('story_ref::admin.audiences.validation.slug_unique'),
            'order.required' => __('story_ref::admin.audiences.validation.order_required'),
            'order.integer' => __('story_ref::admin.audiences.validation.order_integer'),
            'threshold_age.integer' => __('story_ref::admin.audiences.validation.threshold_integer'),
            'threshold_age.min' => __('story_ref::admin.audiences.validation.threshold_min'),
            'threshold_age.max' => __('story_ref::admin.audiences.validation.threshold_max'),
        ];
    }
}
