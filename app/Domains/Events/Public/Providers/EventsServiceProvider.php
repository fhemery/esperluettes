<?php

namespace App\Domains\Events\Public\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Events\Public\Api\EventPublicApi;
use App\Domains\Events\Private\Services\DomainEventFactory;
use App\Domains\Events\Private\Services\EventService;

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
        // In the future, we could register internal listeners here if needed.
    }
}
