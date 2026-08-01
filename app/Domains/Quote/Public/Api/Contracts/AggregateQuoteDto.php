<?php

namespace App\Domains\Quote\Public\Api\Contracts;

use App\Domains\Shared\Dto\ProfileDto;

/**
 * A single quote of a chapter, as seen by its author.
 *
 * It deliberately carries no note property: the reader's note is private, and
 * a field that does not exist cannot leak.
 */
class AggregateQuoteDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $highlightedText,
        public readonly ?string $prefix,
        public readonly ?string $suffix,
        public readonly \DateTimeInterface $createdAt,
        public readonly ProfileDto $quoter,
    ) {
    }
}
