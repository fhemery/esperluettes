<?php

namespace App\Domains\Story\Http\Requests;

use App\Domains\Story\Models\Story;
use Illuminate\Foundation\Http\FormRequest;

class StoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // must be authenticated
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'visibility' => ['required', 'in:' . implode(',', Story::visibilityOptions())],
        ];
    }
}
