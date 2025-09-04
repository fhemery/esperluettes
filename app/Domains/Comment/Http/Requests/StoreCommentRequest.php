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
}
