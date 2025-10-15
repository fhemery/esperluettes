<?php

namespace App\Domains\Config\Public\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Config\Public\Events\FeatureToggleAdded;
use App\Domains\Config\Public\Events\FeatureToggleDeleted;
use App\Domains\Config\Public\Events\FeatureToggleUpdated;

class ConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load migrations for the Config domain
        $this->loadMigrationsFrom(app_path('Domains/Config/Database/Migrations'));
        // Load translations for the Config domain
        $this->loadTranslationsFrom(app_path('Domains/Config/Public/Resources/lang'), 'config');

        // Register Config domain events
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(FeatureToggleAdded::name(), FeatureToggleAdded::class);
        $eventBus->registerEvent(FeatureToggleDeleted::name(), FeatureToggleDeleted::class);
        $eventBus->registerEvent(FeatureToggleUpdated::name(), FeatureToggleUpdated::class);
    }
}
