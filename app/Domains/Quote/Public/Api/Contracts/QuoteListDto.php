<?php

namespace App\Domains\Quote\Public\Api\Contracts;

class QuoteListDto
{
    /**
     * @param QuoteDto[] $items
     */
    public function __construct(
        public readonly array $items,
        public readonly bool $viewerIsOwner,
        public readonly bool $canQuote,
        public readonly int $page,
        public readonly int $totalCount,
    ) {
    }
}
