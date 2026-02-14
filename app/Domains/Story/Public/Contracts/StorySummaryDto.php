<?php

namespace App\Domains\Story\Public\Contracts;

use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Services\CoverService;

class StorySummaryDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $visibility,
        public string $cover_type,
        public string $cover_url,
        public int $word_count,
    ) {
    }

    public static function fromModel(Story $story, ?CoverService $coverService = null): self
    {
        $coverService ??= app(CoverService::class);

        return new self(
            id: (int) $story->id,
            title: (string) $story->title,
            slug: (string) $story->slug,
            visibility: (string) $story->visibility,
            cover_type: (string) ($story->cover_type ?? Story::COVER_DEFAULT),
            cover_url: $coverService->getCoverUrl($story),
            word_count: (int) $story->chapters()->sum('word_count'),
        );
    }
}
