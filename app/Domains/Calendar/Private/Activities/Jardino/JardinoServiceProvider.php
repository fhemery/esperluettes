<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Private\Activities\Jardino;

use App\Domains\Calendar\Private\Activities\Jardino\Listeners\UpdateSnapshotWordCount;
use App\Domains\Calendar\Private\Activities\Jardino\Services\JardinoProgressService;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\ChapterDeleted;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class JardinoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bind the single listener with its service dependency
        $this->app->bind(UpdateSnapshotWordCount::class, function ($app) {
            return new UpdateSnapshotWordCount(
                $app->make(JardinoProgressService::class)
            );
        });
    }

    public function boot(): void
    {
        // Views for Jardino-specific components (namespace: 'jardino')
        $this->loadViewsFrom(app_path('Domains/Calendar/Private/Activities/Jardino/Resources/views'), 'jardino');

        // Translations (keep using the 'calendar' namespace for unified lookups)
        $this->loadTranslationsFrom(app_path('Domains/Calendar/Private/Activities/Jardino/Resources/lang'), 'jardino');

        // Load migrations and routes for the Jardino activity
        $this->loadMigrationsFrom(app_path('Domains/Calendar/Private/Activities/Jardino/Database/Migrations'));
        $this->loadRoutesFrom(app_path('Domains/Calendar/Private/Activities/Jardino/Http/routes.php'));

        // Register a component namespace so 'jardino::jardino-component' resolves to the class
        Blade::componentNamespace('App\\Domains\\Calendar\\Private\\Activities\\Jardino\\View\\Components', 'jardino');

        // Register event listeners for real-time progress updates
        $this->registerEventListeners();
    }

    private function registerEventListeners(): void
    {
        /** @var EventBus $eventBus */
        $eventBus = app(EventBus::class);

        $listener = app(UpdateSnapshotWordCount::class);

        // Register listeners for chapter events from Story domain
        $eventBus->subscribe(ChapterCreated::class, [$listener, 'handleChapterCreated']);
        $eventBus->subscribe(ChapterUpdated::class, [$listener, 'handleChapterUpdated']);
        $eventBus->subscribe(ChapterDeleted::class, [$listener, 'handleChapterDeleted']);
    }
}
