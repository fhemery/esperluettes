<?php

namespace App\Domains\Quote\Public\Api\Contracts;

class ChapterAggregateDto
{
    /**
     * @param AggregateQuoteDto[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly int $totalCount,
    ) {
    }
}
