<?php

namespace App\Domains\Story\Private\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderChaptersRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Controller will enforce authorship with Story context; allow here.
        return true;
    }

    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array', 'min:1'],
            'ordered_ids.*' => ['integer'],
        ];
    }
}
