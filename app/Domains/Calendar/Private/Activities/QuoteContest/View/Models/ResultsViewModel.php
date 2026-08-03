<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

/**
 * Everything the *Résultats* tab renders. It is built only for a moderator or
 * an admin, and the tab it feeds is absent from the tabs array for everyone
 * else — never rendered and then hidden (architecture §3.3, point 4).
 *
 * The tab is permanent: it carries no phase, because it reads the same before,
 * during and after the contest.
 */
final class ResultsViewModel
{
    /** @param array<int, ResultsCategoryViewModel> $categories */
    public function __construct(
        /** The moderation delete route is built from it. */
        public readonly int $activityId,
        public readonly array $categories,
    ) {}

    public function hasCategories(): bool
    {
        return $this->categories !== [];
    }
}
