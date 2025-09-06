<?php

declare(strict_types=1);

namespace App\Domains\Comment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Auth is handled via middleware; allow validation to run
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string'],
            'entity_id' => ['required', 'integer'],
            'body' => ['required', 'string'],
            'parent_comment_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * Normalize numeric inputs so they are integers in the input bag prior to validation.
     */
    protected function prepareForValidation(): void
    {
        $entityId = $this->input('entity_id');
        $parentId = $this->input('parent_comment_id');

        $this->merge([
            'entity_id' => isset($entityId) ? (int) $entityId : null,
            // Keep null when not provided; cast to int when provided (even if a numeric string)
            'parent_comment_id' => $parentId === null || $parentId === '' ? null : (int) $parentId,
        ]);
    }
}
