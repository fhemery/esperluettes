<?php

namespace App\Domains\Notification\Public\Providers;

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
    }
}
