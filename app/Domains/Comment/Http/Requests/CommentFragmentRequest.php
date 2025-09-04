<?php

declare(strict_types=1);

namespace App\Domains\Comment\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CommentFragmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Access controlled via middleware
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required','string'],
            'entity_id' => ['required','integer'],
            'page' => ['sometimes','integer','min:1'],
            'per_page' => ['sometimes','integer','min:1','max:100'],
        ];
    }
}
