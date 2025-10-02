<?php

declare(strict_types=1);

namespace App\Domains\Message\Private\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DeleteMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization handled via policy
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
