<?php

namespace App\Domains\Config\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Public\Events\ConfigParameterUpdated;
use App\Domains\Config\Public\Events\FeatureToggleAdded;
use App\Domains\Config\Public\Events\FeatureToggleDeleted;
use App\Domains\Config\Public\Events\FeatureToggleUpdated;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load migrations for the Config domain
        $this->loadMigrationsFrom(app_path('Domains/Config/Database/Migrations'));

        // Load routes
        $this->loadRoutesFrom(app_path('Domains/Config/Private/routes.php'));

        // Load views under the 'config' namespace
        $this->loadViewsFrom(app_path('Domains/Config/Private/Resources/views'), 'config');

        // Register anonymous components
        Blade::anonymousComponentPath(app_path('Domains/Config/Private/Resources/views/components'), 'config');

        // Load translations for the Config domain
        $this->loadTranslationsFrom(app_path('Domains/Config/Public/Resources/lang'), 'config');

        // Register Config domain events
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(FeatureToggleAdded::name(), FeatureToggleAdded::class);
        $eventBus->registerEvent(FeatureToggleDeleted::name(), FeatureToggleDeleted::class);
        $eventBus->registerEvent(FeatureToggleUpdated::name(), FeatureToggleUpdated::class);
        $eventBus->registerEvent(ConfigParameterUpdated::name(), ConfigParameterUpdated::class);

        // Register admin navigation
        $this->registerAdminNavigation();
    }

    protected function registerAdminNavigation(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerPage(
            key: 'config.parameters',
            group: 'config',
            label: __('config::admin.parameters.nav_label'),
            target: AdminRegistryTarget::route('config.admin.parameters.index'),
            icon: 'tune',
            permissions: [Roles::ADMIN, Roles::TECH_ADMIN],
            sortOrder: 20,
        );
    }
}
