<?php

namespace App\Domains\Settings\Public\Providers;

use App\Domains\Settings\Private\Services\SettingsRegistryService;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The registry holds in-memory definitions shared by every consumer
        // (SettingsPublicApi, SettingsService, SettingsController), so it must
        // resolve to a single instance per application boot.
        $this->app->singleton(SettingsRegistryService::class);
    }

    public function boot(): void
    {
        // Load migrations for the Settings domain
        $this->loadMigrationsFrom(app_path('Domains/Settings/Database/Migrations'));

        // Load routes
        $this->loadRoutesFrom(app_path('Domains/Settings/Private/routes.php'));

        // Load views under the 'settings' namespace
        $this->loadViewsFrom(app_path('Domains/Settings/Private/Resources/views'), 'settings');

        // Register anonymous components
        Blade::anonymousComponentPath(app_path('Domains/Settings/Private/Resources/views/components'), 'settings');

        // Load translations for the Settings domain
        $this->loadTranslationsFrom(app_path('Domains/Settings/Private/Resources/lang'), 'settings');
    }
}
