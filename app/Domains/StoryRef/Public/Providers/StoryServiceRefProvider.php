<?php

namespace App\Domains\StoryRef\Public\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;

class StoryServiceRefProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register StoryRef domain services here if needed.
    }

    public function boot(): void
    {
        // Load migrations from the StoryRef domain root
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');
        // PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../../Private/Resources/lang', 'story_ref');

        // Register StoryRef domain events mapping with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(StoryRefAdded::name(), StoryRefAdded::class);
        $eventBus->registerEvent(StoryRefUpdated::name(), StoryRefUpdated::class);
        $eventBus->registerEvent(StoryRefRemoved::name(), StoryRefRemoved::class);
    }
}
