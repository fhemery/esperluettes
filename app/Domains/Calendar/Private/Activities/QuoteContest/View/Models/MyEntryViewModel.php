<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

/**
 * The reader's own entry in one category, as the reader-facing screens see it.
 *
 * Architecture §3.3: anonymity is a query-shape guarantee, not a template one.
 * This object carries **no submitter identity** — a template mistake therefore
 * cannot leak one, because there is nothing to leak.
 */
final class MyEntryViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $highlightedText,
        public readonly string $storyTitle,
        public readonly string $storyUrl,
        public readonly string $chapterTitle,
        public readonly string $chapterUrl,
    ) {}
}
