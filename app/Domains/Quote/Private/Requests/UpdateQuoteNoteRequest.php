<?php

namespace App\Domains\Quote\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
