<?php

namespace App\Domains\Story\Private\ViewModels;

class ChapterSummaryViewModel
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly bool $isDraft,
        public readonly bool $isRead,
        public readonly int $readsLogged,
        public readonly int $wordCount,
        public readonly int $characterCount,
        public readonly string $url,
    ) {
    }
}
