<?php

namespace App\Domains\StaticPage\Observers;

use App\Domains\StaticPage\Models\StaticPage;
use App\Domains\StaticPage\Services\StaticPageService;

class StaticPageObserver
{
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
    }
}
