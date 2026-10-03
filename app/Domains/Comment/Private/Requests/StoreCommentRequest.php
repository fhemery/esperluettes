<?php

declare(strict_types=1);

namespace App\Domains\Comment\Private\Requests;

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
            'annotations' => ['nullable', 'array'],
            'annotations.*.body' => ['required', 'string'],
            'annotations.*.highlighted_text' => ['required', 'string', 'max:500'],
            'annotations.*.prefix' => ['nullable', 'string', 'max:255'],
            'annotations.*.suffix' => ['nullable', 'string', 'max:255'],
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
            'annotations' => $this->decodeAnnotations($this->input('annotations')),
        ]);
    }

    /**
     * The client serialises annotation drafts into one hidden input (JSON string).
     * Empty / missing → []; undecodable → left as is so the `array` rule fails.
     */
    private function decodeAnnotations(mixed $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return [];
        }
        if (!is_string($raw)) {
            return $raw;
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : $raw;
    }
}
