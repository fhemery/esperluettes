<?php

namespace App\Domains\Comment\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape only: errors here are client bugs and stay positional. Item rules (length,
 * ownership, staleness) are checked by AnnotationPublicApi and keyed per item.
 */
class SaveAnnotationChangesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled in AnnotationPublicApi (root comment's author + policy).
        return true;
    }

    public function rules(): array
    {
        return [
            'adds' => ['nullable', 'array'],
            'adds.*.key' => ['required', 'string', 'max:64', 'distinct'],
            'adds.*.body' => ['required', 'string'],
            'adds.*.highlighted_text' => ['required', 'string'],
            'adds.*.prefix' => ['nullable', 'string'],
            'adds.*.suffix' => ['nullable', 'string'],
            'edits' => ['nullable', 'array'],
            'edits.*.id' => ['required', 'integer', 'distinct'],
            'edits.*.body' => ['required', 'string'],
            'deletes' => ['nullable', 'array'],
            'deletes.*' => ['integer', 'distinct'],
        ];
    }
}
