<?php

namespace App\Domains\Comment\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnnotationsInEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Content is filtered by viewer role in AnnotationPublicApi; an unsupported type yields no list.
        return true;
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', 'max:64'],
            'entity_id' => ['required', 'integer', 'min:1'],
        ];
    }
}
