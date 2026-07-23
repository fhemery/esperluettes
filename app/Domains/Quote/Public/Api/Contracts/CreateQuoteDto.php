<?php

namespace App\Domains\Quote\Public\Api\Contracts;

class CreateQuoteDto
{
    public function __construct(
        public readonly int $storyId,
        public readonly string $highlightedText,
        public readonly ?string $prefix = null,
        public readonly ?string $suffix = null,
        public readonly ?string $note = null,
    ) {
    }
}
