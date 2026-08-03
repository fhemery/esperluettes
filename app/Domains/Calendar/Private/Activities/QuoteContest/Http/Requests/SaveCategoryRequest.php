<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route's admin role middleware is the authorization.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:160'],
            // Plain text (assumption A3): no Purifier, no rich-text editor,
            // escaped on render.
            'description' => ['nullable', 'string', 'max:2000'],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => __('quote-contest::quote-contest.validation.category_title_required'),
            'title.max' => __('quote-contest::quote-contest.validation.category_title_max'),
            'description.max' => __('quote-contest::quote-contest.validation.category_description_max'),
            'position.integer' => __('quote-contest::quote-contest.validation.category_position_integer'),
        ];
    }
}
