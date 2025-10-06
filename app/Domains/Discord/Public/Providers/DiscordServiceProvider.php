<?php

namespace App\Domains\Discord\Public\Providers;

use App\Domains\Discord\Public\Events\DiscordConnected;
use App\Domains\Discord\Public\Events\DiscordDisconnected;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Discord\Private\Listeners\RemoveDiscordAssociationsOnUserDeleted;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

class DiscordServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        // Migrations (none yet but keep structure consistent)
        $this->loadMigrationsFrom(app_path('Domains/Discord/Database/Migrations'));

        // Translations and views namespaces (for blade component later)
        $this->loadTranslationsFrom(app_path('Domains/Discord/Private/Resources/lang'), 'discord');
        $this->loadViewsFrom(app_path('Domains/Discord/Private/Resources/views'), 'discord');

        // API routes
        $this->loadRoutesFrom(app_path('Domains/Discord/Private/api.routes.php'));

        // Web routes
        $this->loadRoutesFrom(app_path('Domains/Discord/Private/web.routes.php'));

        // Events
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(DiscordConnected::name(), DiscordConnected::class);
        $eventBus->registerEvent(DiscordDisconnected::name(), DiscordDisconnected::class);
        // Subscribe to Auth.UserDeleted to remove Discord links
        $eventBus->subscribe(UserDeleted::name(), [RemoveDiscordAssociationsOnUserDeleted::class, 'handle']);

        // Blade components namespace
        Blade::componentNamespace('App\\Domains\\Discord\\Private\\Views\\Components', 'discord');

        // Middleware alias
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('discord.api', \App\Domains\Discord\Private\Middleware\DiscordApiAuth::class);
    }
}
