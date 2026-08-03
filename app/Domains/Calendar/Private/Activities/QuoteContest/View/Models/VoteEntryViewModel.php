<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

/**
 * One entry on a reader's ballot.
 *
 * Architecture §3.3: anonymity is a query-shape guarantee, not a template one.
 * This object carries **no submitter identity and no vote count** — a template
 * mistake therefore cannot leak either, because neither is here to print.
 *
 * `$authorNames` are the *authors of the quoted story*, which the story page
 * names publicly anyway; they are resolved live (decision #19), so a renamed
 * author shows their current name and a deleted one is simply absent.
 */
final class VoteEntryViewModel
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
    ) {}

    public function hasAuthorNames(): bool
    {
        return $this->authorNames !== [];
    }
}
