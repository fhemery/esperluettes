<?php

namespace App\Domains\Notification\Public\Providers;

use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Notification\Private\Console\CleanupOldNotificationsCommand;
use App\Domains\Notification\Private\Listeners\CleanNotificationsOnUserDeleted;
use App\Domains\Notification\Public\Events\NotificationsCleanedUp;
use App\Domains\Notification\Public\Services\NotificationFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register NotificationFactory as singleton
        $this->app->singleton(NotificationFactory::class, function () {
            return new NotificationFactory();
        });

        // Register console commands
        $this->commands([
            CleanupOldNotificationsCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(app_path('Domains/Notification/Private/Resources/lang'), 'notifications');
        $this->loadRoutesFrom(app_path('Domains/Notification/Private/routes.php'));

        $this->loadMigrationsFrom(app_path('Domains/Notification/Database/Migrations'));

        // Register views and components
        $this->loadViewsFrom(app_path('Domains/Notification/Private/Resources/views'), 'notification');
        Blade::componentNamespace('App\\Domains\\Notification\\Private\\View\\Components', 'notification');
        Blade::anonymousComponentPath(app_path('Domains/Notification/Private/Resources/views/components'), 'notification');

        // Register Notification domain events with EventBus
        $this->registerEvents();
    }

    private function registerEvents(): void
    {
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(
            NotificationsCleanedUp::name(),
            NotificationsCleanedUp::class
        );

        // Subscribe to cross-domain events
        $eventBus->subscribe(UserDeleted::class, [app(CleanNotificationsOnUserDeleted::class), 'handle']);
    }
}
