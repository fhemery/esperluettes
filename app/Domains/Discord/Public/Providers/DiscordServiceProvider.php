<?php

namespace App\Domains\Discord\Public\Providers;

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

        // Middleware alias
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('discord.api', \App\Domains\Discord\Private\Middleware\DiscordApiAuth::class);
    }
}
