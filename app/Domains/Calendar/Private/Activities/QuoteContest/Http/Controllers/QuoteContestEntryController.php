<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Http\Controllers;

use App\Domains\Calendar\Private\Activities\QuoteContest\Http\Requests\SubmitEntryRequest;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestSubmissionService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\SubmissionRefusedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * The reader's two write actions on a contest: enter a quote in a category
 * (replacing whatever sits there) and withdraw an entry.
 *
 * Every refusal is a 403 and not a redirect: the page never offers an action
 * the reader may not take, so reaching one means the request was forged
 * (architecture §3.3, point 2).
 */
class QuoteContestEntryController
{
    public function __construct(
        private readonly QuoteContestSubmissionService $submissions,
    ) {}

    public function store(SubmitEntryRequest $request, int $activityId): RedirectResponse
    {
        $data = $request->validated();

        try {
            $this->submissions->submit(
                $activityId,
                (int) $data['category_id'],
                (int) $data['quote_id'],
                (int) Auth::id(),
            );
        } catch (SubmissionRefusedException) {
            abort(403);
        }

        return back()->with('success', __('quote-contest::quote-contest.flash.entry_submitted'));
    }

    public function destroy(int $activityId, int $entryId): RedirectResponse
    {
        try {
            $this->submissions->withdraw($entryId, (int) Auth::id());
        } catch (SubmissionRefusedException) {
            abort(403);
        }

        return back()->with('success', __('quote-contest::quote-contest.flash.entry_withdrawn'));
    }
}
