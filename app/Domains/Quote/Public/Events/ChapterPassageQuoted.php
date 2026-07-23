<?php

namespace App\Domains\Quote\Public\Events;

use App\Domains\Events\Public\Contracts\DomainEvent;

class ChapterPassageQuoted implements DomainEvent
{
    public function __construct(
        public readonly int $quoterId,
        public readonly int $chapterId,
        public readonly int $storyId,
        public readonly string $highlightedText,
    ) {
    }
}
