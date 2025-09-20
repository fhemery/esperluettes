<?php

namespace App\Domains\StoryRef\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StoryRef\Events\StoryRefAdded;
use App\Domains\StoryRef\Events\StoryRefUpdated;
use App\Domains\StoryRef\Events\StoryRefRemoved;

class StoryServiceRefProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register StoryRef domain services here if needed.
    }

    public function boot(): void
    {
        // Load migrations from the StoryRef domain
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        // PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'story_ref');

        // Register StoryRef domain events mapping with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(StoryRefAdded::name(), StoryRefAdded::class);
        $eventBus->registerEvent(StoryRefUpdated::name(), StoryRefUpdated::class);
        $eventBus->registerEvent(StoryRefRemoved::name(), StoryRefRemoved::class);
    }
}
