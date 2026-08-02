<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

use App\Domains\Calendar\Private\Activities\QuoteContest\Support\QuoteContestPhase;
use Carbon\CarbonInterface;

/**
 * Everything the *Mes citations* tab renders. One template is driven by
 * `$phase`, so the read-only states and the live one cannot drift apart.
 *
 * `$quotes` is populated only while submissions are open: outside that phase
 * there is nothing to pick, so the picker is not built and not sent.
 */
final class MyQuotesViewModel
{
    /**
     * @param array<int, ContestCategoryViewModel> $categories
     * @param array<int, PickerQuoteViewModel> $quotes
     */
    public function __construct(
        public readonly QuoteContestPhase $phase,
        public readonly array $categories,
        public readonly array $quotes,
        public readonly ?CarbonInterface $submissionsStartAt,
        public readonly ?CarbonInterface $submissionsEndAt,
        public readonly ?CarbonInterface $votesStartAt,
    ) {}

    public function isSubmissionPhase(): bool
    {
        return $this->phase === QuoteContestPhase::Submissions;
    }

    public function hasCategories(): bool
    {
        return $this->categories !== [];
    }
}
