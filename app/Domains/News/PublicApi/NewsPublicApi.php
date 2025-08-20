<?php

namespace App\Domains\News\PublicApi;

use App\Domains\News\Services\NewsService;
use App\Domains\News\PublicApi\Dto\NewsCarouselItemDto;

class NewsPublicApi
{
    public function __construct(
        private NewsService $newsService
    ) {}

    /**
     * Get pinned news items for carousel display
     * 
     * @return array<NewsCarouselItemDto>
     */
    public function getPinnedForCarousel(): array
    {
        return $this->newsService
            ->getPinnedForCarousel()
            ->map(fn($news) => NewsCarouselItemDto::fromModel($news))
            ->all();
    }
}
