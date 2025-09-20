<?php

namespace App\Domains\News\Private\Services;

use App\Domains\News\Private\Models\News;
use App\Domains\Shared\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Mews\Purifier\Facades\Purifier;
use Illuminate\Support\Facades\Cache;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\News\Public\Events\NewsPublished;
use App\Domains\News\Public\Events\NewsUnpublished;

class NewsService
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}

    public function sanitizeContent(string $html): string
    {
        return Purifier::clean($html, 'admin-content');
    }

    public function processHeaderImage(UploadedFile|string|null $file): ?string
    {
        if (!$file) {
            return null;
        }

        $disk = 'public';
        $folder = 'news/' . date('Y/m');

        // Normalize Filament temp array handled at caller; we accept UploadedFile|string here
        $imageService = app(ImageService::class);
        return $imageService->process($disk, $folder, $file, widths: [400, 800]);
    }

    public function publish(News $news): News
    {
        $news->status = 'published';
        if (!$news->published_at) {
            $news->published_at = now();
        }
        $news->save();
        $this->bustCarouselCache();
        // Emit domain event
        $this->eventBus->emit(new NewsPublished(
            newsId: (int) $news->id,
            slug: (string) $news->slug,
            title: (string) $news->title,
            publishedAt: optional($news->published_at)->toISOString(),
        ));
        return $news;
    }

    public function unpublish(News $news): News
    {
        $news->status = 'draft';
        $news->save();
        $this->bustCarouselCache();
        // Emit domain event
        $this->eventBus->emit(new NewsUnpublished(
            newsId: (int) $news->id,
            slug: (string) $news->slug,
            title: (string) $news->title,
        ));
        return $news;
    }

    public function pin(News $news, int $order): News
    {
        $news->is_pinned = true;
        $news->display_order = $order;
        $news->save();
        $this->bustCarouselCache();
        return $news;
    }

    public function unpin(News $news): News
    {
        $news->is_pinned = false;
        $news->display_order = null;
        $news->save();
        $this->bustCarouselCache();
        return $news;
    }

    /**
     * Delete an existing header image and its generated variants.
     */
    public function deleteHeaderImage(?string $headerImagePath): void
    {
        if (!$headerImagePath) {
            return;
        }
        $disk = 'public';
        app(ImageService::class)->deleteWithVariants($disk, $headerImagePath);
    }

    public function bustCarouselCache(): void
    {
        Cache::forget('news.carousel');
    }

    public function getPinnedForCarousel()
    {
        return Cache::remember('news.carousel', 300, function () {
            return News::query()
                ->pinned()
                ->published()
                ->orderBy('display_order', 'asc')
                ->orderByDesc('published_at')
                ->get();
        });
    }
}
