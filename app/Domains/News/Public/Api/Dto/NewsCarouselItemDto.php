<?php

namespace App\Domains\News\Public\Api\Dto;

class NewsCarouselItemDto
{
    public function __construct(
        public string $slug,
        public string $title,
        public string $summary,
        public ?string $header_image_path,
    ) {}

    public static function fromModel($news): self
    {
        return new self(
            slug: $news->slug,
            title: $news->title,
            summary: $news->summary,
            header_image_path: $news->header_image_path,
        );
    }
}
