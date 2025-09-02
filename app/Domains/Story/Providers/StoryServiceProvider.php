<?php

namespace App\Domains\Story\Providers;

use App\Domains\Story\Models\Story;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Policies\StoryPolicy;
use App\Domains\Story\Policies\ChapterPolicy;
use Illuminate\Support\Facades\Gate;
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
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'story');

        // Register policies
        Gate::policy(Story::class, StoryPolicy::class);
        Gate::policy(Chapter::class, ChapterPolicy::class);

    }
}
