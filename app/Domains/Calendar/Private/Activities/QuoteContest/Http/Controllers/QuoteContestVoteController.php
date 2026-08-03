<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\Http\Controllers;

use App\Domains\Calendar\Private\Activities\QuoteContest\Http\Requests\CastVoteRequest;
use App\Domains\Calendar\Private\Activities\QuoteContest\Services\QuoteContestVoteService;
use App\Domains\Calendar\Private\Activities\QuoteContest\Support\VoteRefusedException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * The reader's ballot in one category: cast it, or change it.
 *
 * One idempotent `PUT` on the category — the resource is "this reader's ballot
 * here" — and never a `PATCH`, whose verb the production WAF resets.
 *
 * A refusal is a 403 and not a redirect: the ballot is only rendered while the
 * votes are open and only ever offers live entries, so reaching one of these
 * means the request was forged (architecture §3.3, point 2).
 */
class QuoteContestVoteController
{
    public function __construct(
        private readonly QuoteContestVoteService $votes,
    ) {}

    public function update(CastVoteRequest $request, int $activityId, int $categoryId): RedirectResponse
    {
        try {
            $this->votes->castVote(
                $categoryId,
                (int) $request->validated()['entry_id'],
                (int) Auth::id(),
            );
        } catch (VoteRefusedException) {
            abort(403);
        }

        // Back to the tab the ballot lives in, which the tabs component reads
        // from the hash.
        return back()
            ->withFragment('votes')
            ->with('success', __('quote-contest::quote-contest.flash.vote_cast'));
    }
}
