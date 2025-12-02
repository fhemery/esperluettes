<?php

namespace App\Domains\News\Private\Services;

use App\Domains\Events\Public\Api\EventBus;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Public\Events\NewsPublished;
use App\Domains\News\Public\Events\NewsUnpublished;
use App\Domains\News\Public\Notifications\NewsPublishedNotification;
use App\Domains\Notification\Public\Api\NotificationPublicApi;
use App\Domains\Shared\Services\ImageService;
use App\Domains\Shared\Support\HtmlLinkUtils;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Mews\Purifier\Facades\Purifier;

class NewsService
{
    public function __construct(
        private readonly EventBus $eventBus,
        private readonly NotificationPublicApi $notificationApi,
    ) {}

    public function sanitizeContent(string $html): string
    {
        // Purify HTML then add target="_blank" to external links
        $clean = Purifier::clean($html, 'admin-content');
        return HtmlLinkUtils::addTargetBlankToExternalLinks($clean) ?? $clean;
    }

    /**
     * Create a new news article.
     */
    public function create(array $data): News
    {
        // Sanitize content
        $data['content'] = $this->sanitizeContent($data['content'] ?? '');

        // Process header image if uploaded
        if (!empty($data['header_image']) && $data['header_image'] instanceof UploadedFile) {
            $data['header_image_path'] = $this->processHeaderImage($data['header_image']);
        }
        unset($data['header_image'], $data['header_image_remove']);

        // Set creator
        $data['created_by'] = Auth::id();

        // Handle published_at if publishing
        if (($data['status'] ?? 'draft') === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return News::create($data);
    }

    /**
     * Update an existing news article.
     */
    public function update(News $news, array $data): News
    {
        // Sanitize content
        $data['content'] = $this->sanitizeContent($data['content'] ?? '');

        // Handle header image removal
        if (!empty($data['header_image_remove'])) {
            $this->deleteHeaderImage($news->header_image_path);
            $data['header_image_path'] = null;
        }

        // Process new header image if uploaded
        if (!empty($data['header_image']) && $data['header_image'] instanceof UploadedFile) {
            // Delete old image first
            if ($news->header_image_path) {
                $this->deleteHeaderImage($news->header_image_path);
            }
            $data['header_image_path'] = $this->processHeaderImage($data['header_image']);
        }
        unset($data['header_image'], $data['header_image_remove']);

        // Handle published_at if transitioning to published
        if (($data['status'] ?? 'draft') === 'published' && !$news->published_at) {
            $data['published_at'] = now();
        }

        $news->update($data);

        return $news;
    }

    /**
     * Delete a news article and its associated resources.
     */
    public function delete(News $news): void
    {
        // Delete header image if exists
        if ($news->header_image_path) {
            $this->deleteHeaderImage($news->header_image_path);
        }

        // Bust cache if it was pinned
        if ($news->is_pinned) {
            $this->bustCarouselCache();
        }

        $news->delete();
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

        // Broadcast notification to all users (system notification)
        $this->notificationApi->createBroadcastNotification(
            new NewsPublishedNotification(
                newsTitle: (string) $news->title,
                newsSlug: (string) $news->slug,
            ),
            sourceUserId: null, // System notification
        );

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

    /**
     * Nullify created_by for all news authored by the given user.
     * Returns affected rows count.
     */
    public function nullifyCreator(int $userId): int
    {
        return News::query()
            ->where('created_by', $userId)
            ->update(['created_by' => null]);
    }
}
