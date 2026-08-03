<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;
use Carbon\CarbonInterface;

/**
 * Everything the *Votes* tab renders. One template is driven by `$phase`, so
 * the open ballot and the read-only one cannot drift apart.
 *
 * `$categories` is populated only from the vote phase onwards: before the votes
 * open there is nothing to choose between, so the ballot is neither built nor
 * sent (the same economy as the picker's, assumption A22).
 */
final class VotesViewModel
{
    /** @param array<int, VoteCategoryViewModel> $categories */
    public function __construct(
        /** The vote route is built from it. */
        public readonly int $activityId,
        public readonly QuoteContestPhase $phase,
        public readonly array $categories,
        public readonly ?CarbonInterface $votesStartAt,
        public readonly ?CarbonInterface $votesEndAt,
    ) {}

    /** Votes may be cast and changed: the vote phase, and only it. */
    public function isOpen(): bool
    {
        return $this->phase === QuoteContestPhase::Voting;
    }

    public function hasBallot(): bool
    {
        return $this->categories !== [];
    }
}
