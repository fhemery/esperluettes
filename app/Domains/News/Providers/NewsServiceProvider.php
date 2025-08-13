<?php

namespace App\Domains\News\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\News\Models\News;
use App\Domains\News\Observers\NewsObserver;

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
    }
}
