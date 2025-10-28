<?php

namespace App\Domains\Story\Public\Contracts;

use App\Domains\Story\Private\Models\Story;

class StoryDto
{
    public function __construct(
        public int $id,
        public string $title,
        public string $slug,
        public string $visibility,
        public string $cover_url,
        public int $word_count,
    ) {
    }

    public static function fromModel(Story $story): self
    {
        return new self(
            id: (int) $story->id,
            title: (string) $story->title,
            slug: (string) $story->slug,
            visibility: (string) $story->visibility,
            cover_url: (string) $story->cover_url,
            word_count: (int) $story->chapters()->sum('word_count'),
        );
    }
}
