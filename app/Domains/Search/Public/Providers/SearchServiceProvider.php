<?php

namespace App\Domains\Search\Public\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Blade;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Nothing to bind yet; Search uses shared contracts from other domains
    }

    public function boot(): void
    {
        // Register domain routes
        $this->loadRoutesFrom(app_path('Domains/Search/Private/routes.php'));

        // Register translations
        $this->loadTranslationsFrom(app_path('Domains/Search/Private/Resources/lang'), 'search');


        Blade::anonymousComponentPath(app_path('Domains/Search/Private/Resources/views'), 'search');
        View::addNamespace('search', app_path('Domains/Search/Private/Resources/views'));

        Carbon::setLocale(app()->getLocale());
    }
}
