<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape only. Ownership of the quote, eligibility of its story and the phase of
 * the contest are the submission service's job: they are authorization rules,
 * and they must hold for a request that never went through this form.
 */
class SubmitEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'integer'],
            'quote_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => __('quote-contest::quote-contest.validation.category_required'),
            'quote_id.required' => __('quote-contest::quote-contest.validation.quote_required'),
        ];
    }
}
