<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

/**
 * One contest category as the reader sees it, with the entry they currently
 * hold in it (if any). Never carries anybody else's entry.
 */
final class ContestCategoryViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly ?MyEntryViewModel $myEntry,
    ) {}
}
