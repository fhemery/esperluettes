<?php

namespace App\Domains\News\Public\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Observers\NewsObserver;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\News\Public\Events\NewsPublished;
use App\Domains\News\Public\Events\NewsUpdated;
use App\Domains\News\Public\Events\NewsDeleted;
use App\Domains\News\Public\Events\NewsUnpublished;

class NewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load PHP namespaced translations for the News domain
        $this->loadTranslationsFrom(app_path('Domains/News/Private/Resources/lang'), 'news');

        // Register view namespace for the News domain (Private resources)
        View::addNamespace('news', app_path('Domains/News/Private/Resources/views'));

        // Load migrations
        $this->loadMigrationsFrom(app_path('Domains/News/Database/migrations'));

        // Load routes
        $this->loadRoutesFrom(app_path('Domains/News/Private/routes.php'));

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
