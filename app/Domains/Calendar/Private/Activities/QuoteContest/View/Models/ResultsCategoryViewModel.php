<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

/**
 * One category of the *Résultats* table: every entry it ever held, withdrawn
 * ones included, ordered by vote count and then by submission order.
 */
final class ResultsCategoryViewModel
{
    /** @param array<int, ResultsEntryViewModel> $entries */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly array $entries,
    ) {}

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }
}
