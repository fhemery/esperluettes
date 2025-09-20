<?php

namespace App\Domains\News\Private\Observers;

use App\Domains\News\Private\Models\News;
use App\Domains\Shared\Services\ImageService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\News\Public\Events\NewsUpdated;
use App\Domains\News\Public\Events\NewsDeleted;

class NewsObserver
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}

    public function creating(News $news): void
    {
        // Auto-assign display_order if pinned and order not provided
        if ($news->is_pinned && empty($news->display_order)) {
            $news->display_order = $this->nextDisplayOrder();
        }
    }

    public function updating(News $news): void
    {
        // If toggling pin state
        if ($news->isDirty('is_pinned')) {
            $newPinned = (bool) $news->is_pinned;
            $oldPinned = (bool) $news->getOriginal('is_pinned');

            if ($newPinned && !$oldPinned) {
                // Became pinned: ensure it has an order
                if (empty($news->display_order)) {
                    $news->display_order = $this->nextDisplayOrder();
                }
            } elseif (!$newPinned && $oldPinned) {
                // Became unpinned: clear order
                $news->display_order = null;
            }
        } elseif ($news->is_pinned && empty($news->display_order)) {
            // Still pinned but no order set yet: assign one
            $news->display_order = $this->nextDisplayOrder();
        }
    }

    /**
     * Bust carousel cache when relevant fields change or records change lifecycle.
     */
    public function created(News $news): void
    {
        $this->bustIfRelevant($news, true);
    }

    public function updated(News $news): void
    {
        $this->bustIfRelevant($news);

        // Emit News.Updated with changed fields
        $changes = array_keys($news->getChanges());
        if (!empty($changes)) {
            $this->eventBus->emit(new NewsUpdated(
                newsId: (int) $news->id,
                slug: (string) $news->slug,
                title: (string) $news->title,
                changedFields: $changes,
            ));
        }
    }

    public function deleted(News $news): void
    {
        // Delete header image and its variants if present
        if (!empty($news->header_image_path)) {
            app(ImageService::class)->deleteWithVariants('public', $news->header_image_path);
        }
        Cache::forget('news.carousel');

        // Emit News.Deleted
        $this->eventBus->emit(new NewsDeleted(
            newsId: (int) $news->id,
            slug: (string) $news->slug,
            title: (string) $news->title,
        ));
    }

    public function restored(News $news): void
    {
        Cache::forget('news.carousel');
    }

    protected function nextDisplayOrder(): int
    {
        $max = News::query()->where('is_pinned', true)->max('display_order');
        return is_null($max) ? 1 : ((int) $max + 1);
    }

    protected function bustIfRelevant(News $news, bool $onCreate = false): void
    {
        // Carousel depends on: is_pinned, display_order, status, published_at
        if ($onCreate || (bool) $news->is_pinned || $news->wasChanged(['is_pinned', 'display_order', 'status', 'published_at'])) {
            Cache::forget('news.carousel');
        }
    }
}
