<?php

namespace App\Domains\Quote\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateQuoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chapter_id' => ['required', 'integer', 'min:1'],
            'story_id' => ['required', 'integer', 'min:1'],
            'highlighted_text' => ['required', 'string', 'max:' . config('quote.highlighted_text_max_length')],
            'prefix' => ['nullable', 'string', 'max:255'],
            'suffix' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'chapter_id' => $this->input('chapter_id') ? (int) $this->input('chapter_id') : null,
            'story_id' => $this->input('story_id') ? (int) $this->input('story_id') : null,
        ]);
    }
}
