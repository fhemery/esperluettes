<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\QuoteContest\View\Models;

/**
 * One category of a reader's ballot: its entries, already shuffled for this
 * reader, and the one entry this reader voted for — nobody else's.
 *
 * There is no vote count here and no abstention flag: "has not voted" is simply
 * `myVoteEntryId === null` (assumption A10).
 */
final class VoteCategoryViewModel
{
    /** @param array<int, VoteEntryViewModel> $entries */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly ?string $description,
        public readonly array $entries,
        public readonly ?int $myVoteEntryId,
    ) {}

    public function hasVoted(): bool
    {
        return $this->myVoteEntryId !== null;
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }
}
