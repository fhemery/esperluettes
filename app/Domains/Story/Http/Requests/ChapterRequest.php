<?php

namespace App\Domains\Story\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'title' => ['required', 'string', 'max:255'],
            'author_note' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'published' => ['nullable', 'boolean'],
        ];
    }
}
