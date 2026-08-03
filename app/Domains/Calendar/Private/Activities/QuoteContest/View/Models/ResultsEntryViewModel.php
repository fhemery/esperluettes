<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

/**
 * One row of the *Résultats* table: an entry, what it scored, and who put it
 * there.
 *
 * This is the **only** view model of the contest that carries a submitter
 * identity, and the only one that carries a vote count. Architecture §3.3 makes
 * the anonymity of decision #2 a query-shape guarantee: the reader-facing models
 * have nowhere to put either, so a template mistake cannot leak them. Keeping
 * that true means never widening `VoteEntryViewModel` or `MyEntryViewModel`
 * with the two fields below.
 *
 * A submitter who no longer exists resolves to `null` on both fields and the
 * entry still stands (decision #7).
 */
final class ResultsEntryViewModel
{
    /** @param array<int, string> $authorNames */
    public function __construct(
        public readonly int $id,
        public readonly string $highlightedText,
        public readonly string $storyTitle,
        public readonly string $storyUrl,
        public readonly string $chapterTitle,
        public readonly string $chapterUrl,
        public readonly array $authorNames,
        public readonly int $voteCount,
        public readonly ?string $submitterName,
        public readonly ?string $submitterUrl,
        /** Withdrawn for privacy (§2.3): shown, but counted nowhere. */
        public readonly bool $isWithdrawn,
    ) {}

    public function hasAuthorNames(): bool
    {
        return $this->authorNames !== [];
    }

    public function hasSubmitter(): bool
    {
        return $this->submitterName !== null;
    }
}
