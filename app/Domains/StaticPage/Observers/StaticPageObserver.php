<?php

namespace App\Domains\StaticPage\Observers;

use App\Domains\StaticPage\Models\StaticPage;
use App\Domains\StaticPage\Services\StaticPageService;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\StaticPage\Events\StaticPageUpdated;
use App\Domains\StaticPage\Events\StaticPageDeleted;

class StaticPageObserver
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}
    protected function service(): StaticPageService
    {
        return app(StaticPageService::class);
    }

    public function saved(StaticPage $page): void
    {
        $this->service()->rebuildSlugMapCache();
    }

    public function deleted(StaticPage $page): void
    {
        $this->service()->rebuildSlugMapCache();
        // Emit deletion event
        $this->eventBus->emit(new StaticPageDeleted(
            pageId: (int) $page->id,
            slug: (string) $page->slug,
            title: (string) $page->title,
        ));
    }

    public function updated(StaticPage $page): void
    {
        // Emit update event with changed fields
        $changes = array_keys($page->getChanges());
        if (!empty($changes)) {
            $this->eventBus->emit(new StaticPageUpdated(
                pageId: (int) $page->id,
                slug: (string) $page->slug,
                title: (string) $page->title,
                changedFields: $changes,
            ));
        }
    }
}
