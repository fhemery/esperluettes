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
            // Media image-field payload: a new upload (file) xor a reused/kept path.
            'image' => ['nullable', 'array'],
            'image.file' => ['nullable', 'image', 'max:2048'],
            'image.path' => ['nullable', 'string', 'max:1024'],
            'image.alt' => ['nullable', 'string', 'max:255'],
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
