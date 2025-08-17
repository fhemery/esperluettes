<?php

namespace App\Domains\Story\Providers;

use Illuminate\Support\ServiceProvider;

class StoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings for Story domain can be added here later
    }

    public function boot(): void
    {
        // Load domain migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Register views under the 'story' namespace
        $this->loadViewsFrom(app_path('Domains/Story/Views'), 'story');

        // Load PHP translations for the Story domain under 'story::'
        $this->loadTranslationsFrom(app_path('Domains/Story/Resources/lang'), 'story');
    }
}
