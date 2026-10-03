<?php

namespace App\Domains\Comment\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SetAnnotationProcessedRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled in AnnotationPublicApi (author/co-author per policy).
        return true;
    }

    public function rules(): array
    {
        return [
            'value' => ['required', 'boolean'],
        ];
    }
}
