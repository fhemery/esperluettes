<?php

namespace App\Domains\StaticPage\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\StaticPage\Models\StaticPage;
use App\Domains\StaticPage\Observers\StaticPageObserver;

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
    }
}
