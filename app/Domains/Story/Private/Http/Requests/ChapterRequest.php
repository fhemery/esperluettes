<?php

namespace App\Domains\Story\Private\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Mews\Purifier\Facades\Purifier;

class ChapterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Controller handles 404 authorization; request itself allows validation
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required_trimmed', 'max:255'],
            'author_note' => ['nullable', 'maxstripped:1000'],
            'content' => ['required'],
            'published' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $title = $this->input('title');
        if (is_string($title)) {
            $title = trim($title);
        }

        $authorNote = $this->input('author_note');
        if (trim(strip_tags($authorNote)) === '') {
            $authorNote = null;
        }
        $content = $this->input('content');

        $this->merge([
            'title' => $title,
            'author_note' => $authorNote !== null ? Purifier::clean((string) $authorNote, 'strict') : null,
            'content' => Purifier::clean((string) ($content ?? ''), 'strict'),
        ]);
    }

    public function messages(): array
    {
        return [
            'title.required_trimmed' => __('story::validation.chapter.title.required'),
            'author_note.maxstripped' => __('story::validation.chapter.author_note_too_long'),
            'content.required' => __('story::validation.chapter.content.required'),
        ];
    }
}
