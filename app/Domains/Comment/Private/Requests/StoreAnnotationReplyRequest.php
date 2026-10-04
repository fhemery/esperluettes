<?php

namespace App\Domains\Comment\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnnotationReplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled in AnnotationPublicApi (reply rule per role).
        return true;
    }

    public function rules(): array
    {
        // Length is checked in AnnotationPublicApi against the plain text and the policy cap.
        return [
            'body' => ['required', 'string'],
        ];
    }
}
