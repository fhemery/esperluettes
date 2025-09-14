<?php

namespace App\Domains\News\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\News\Models\News;
use App\Domains\News\Observers\NewsObserver;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\News\Events\NewsPublished;
use App\Domains\News\Events\NewsUpdated;
use App\Domains\News\Events\NewsDeleted;
use App\Domains\News\Events\NewsUnpublished;

class NewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load PHP namespaced translations for the News public domain
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'news');

        // Load namespaced views for the News public domain
        $this->loadViewsFrom(__DIR__ . '/../Views', 'news');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        // Model observers
        News::observe(NewsObserver::class);

        // Register News domain events mapping with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(NewsPublished::name(), NewsPublished::class);
        $eventBus->registerEvent(NewsUpdated::name(), NewsUpdated::class);
        $eventBus->registerEvent(NewsDeleted::name(), NewsDeleted::class);
        $eventBus->registerEvent(NewsUnpublished::name(), NewsUnpublished::class);
    }
}
