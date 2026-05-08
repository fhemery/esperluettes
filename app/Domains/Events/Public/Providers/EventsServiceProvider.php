<?php

namespace App\Domains\Events\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Events\Private\Services\DomainEventFactory;
use App\Domains\Events\Private\Services\EventService;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Events\Public\Api\EventPublicApi;
use Illuminate\Support\ServiceProvider;

class EventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Singletons to ensure shared state across resolutions
        // This is important because Laravel by default create transient classes
        $this->app->singleton(DomainEventFactory::class, DomainEventFactory::class);
        $this->app->singleton(EventService::class, EventService::class);
        $this->app->singleton(EventBus::class, function ($app) {
            return new EventBus(
                $app->make(DomainEventFactory::class),
                $app->make(EventService::class),
            );
        });
        $this->app->singleton(EventPublicApi::class, function ($app) {
            return new EventPublicApi(
                $app->make(EventService::class),
                $app->make(DomainEventFactory::class),
            );
        });
        // Register domain-specific migrations
        $this->loadMigrationsFrom(app_path('Domains/Events/Database/Migrations'));
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(app_path('Domains/Events/Private/routes.php'));
        $this->loadTranslationsFrom(app_path('Domains/Events/Private/Resources/lang'), 'events');
        $this->loadViewsFrom(app_path('Domains/Events/Private/Resources/views'), 'events');

        $this->registerAdminNavigation();
    }

    protected function registerAdminNavigation(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerPage(
            'events.admin.domain-events',
            'events',
            __('events::admin.domain_events.nav_label'),
            AdminRegistryTarget::route('events.admin.domain-events.index'),
            'bolt',
            [Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR],
            99,
        );
    }
}
