<?php

namespace App\Domains\StaticPage\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\StaticPage\Models\StaticPage;
use App\Domains\StaticPage\Observers\StaticPageObserver;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\StaticPage\Events\StaticPagePublished;
use App\Domains\StaticPage\Events\StaticPageUnpublished;
use App\Domains\StaticPage\Events\StaticPageUpdated;
use App\Domains\StaticPage\Events\StaticPageDeleted;

class StaticPageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load namespaced translations for the Static Page public domain
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'static');

        // Load namespaced views for the Static Page public domain
        $this->loadViewsFrom(__DIR__ . '/../Views', 'static');

        // Load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/migrations');

        // Model observers
        StaticPage::observe(StaticPageObserver::class);

        // Register StaticPage domain events mapping with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(StaticPagePublished::name(), StaticPagePublished::class);
        $eventBus->registerEvent(StaticPageUnpublished::name(), StaticPageUnpublished::class);
        $eventBus->registerEvent(StaticPageUpdated::name(), StaticPageUpdated::class);
        $eventBus->registerEvent(StaticPageDeleted::name(), StaticPageDeleted::class);
    }
}
