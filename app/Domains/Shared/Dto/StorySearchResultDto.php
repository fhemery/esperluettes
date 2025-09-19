<?php

namespace App\Domains\Shared\Dto;

class StorySearchResultDto
{
    /**
     * @param string[] $authors  // display names
     */
    public function __construct(
        public readonly int $id,
        public readonly string $title,
        public readonly string $slug,
        public readonly ?string $cover_url,
        public readonly array $authors,
        public readonly string $url
    ) {
    }
}
