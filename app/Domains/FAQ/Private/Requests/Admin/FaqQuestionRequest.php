<?php

namespace App\Domains\FAQ\Private\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FaqQuestionRequest extends FormRequest
{
    public function rules(): array
    {
        $questionId = $this->route('faq_question')?->id;

        return [
            'faq_category_id' => ['required', 'integer', 'exists:faq_categories,id'],
            'question' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9\-]+$/',
                Rule::unique('faq_questions', 'slug')->ignore($questionId),
            ],
            'answer' => ['required', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
