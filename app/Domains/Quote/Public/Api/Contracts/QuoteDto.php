<?php

namespace App\Domains\Quote\Public\Api\Contracts;

class QuoteDto
{
    public function __construct(
        public readonly int $id,
        public readonly int $chapterId,
        public readonly int $storyId,
        public readonly string $highlightedText,
        public readonly ?string $prefix,
        public readonly ?string $suffix,
        public readonly ?string $note,
        public readonly ?string $storyTitle,
        public readonly ?string $storyUrl,
        public readonly ?string $chapterTitle,
        public readonly ?string $chapterUrl,
        public readonly array $authorProfiles,
        public readonly \DateTimeInterface $createdAt,
        public readonly bool $canEditNote,
        public readonly bool $canDelete,
        public readonly bool $chapterAvailable,
        public readonly bool $anchorMissing,
    ) {
    }
}
