<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Shape only. Which entries are votable, and whether the ballot is open at all,
 * are the vote service's job: they are authorization rules, and they must hold
 * for a request that never went through this form.
 */
class CastVoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'entry_id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'entry_id.required' => __('quote-contest::quote-contest.validation.entry_required'),
        ];
    }
}
