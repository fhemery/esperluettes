<?php

namespace App\Domains\ReadList\Public\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\ReadList\Public\Notifications\ReadListAddedNotification;

class ReadListServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load domain migrations
        $this->loadMigrationsFrom(app_path('Domains/ReadList/Database/Migrations'));

        // Load routes
        $this->loadRoutesFrom(app_path('Domains/ReadList/Private/routes.php'));

        // Register views under the 'read-list' namespace from Private resources
        $this->loadViewsFrom(app_path('Domains/ReadList/Private/Resources/views'), 'read-list');

        // Register PHP components
        Blade::componentNamespace('App\\Domains\\ReadList\\Private\\View\\Components', 'read-list');

        // Register anonymous components with prefix (<x-read-list::...>)
        Blade::anonymousComponentPath(app_path('Domains/ReadList/Private/Resources/views/components'), 'read-list');

        // Load PHP translations for the ReadList domain under 'readlist::'
        $this->loadTranslationsFrom(app_path('Domains/ReadList/Private/Resources/lang'), 'readlist');

        // Register notification content types
        $factory = app(NotificationFactory::class);
        $factory->register(
            type: ReadListAddedNotification::type(),
            class: ReadListAddedNotification::class
        );
    }
}
