<?php

namespace App\Domains\Moderation\Public\Providers;

use App\Domains\Moderation\Public\Services\ModerationRegistry;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ModerationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register ModerationRegistry as a singleton
        $this->app->singleton(ModerationRegistry::class);
    }

    public function boot(): void
    {
        // Register domain-specific migrations
        $this->loadMigrationsFrom(app_path('Domains/Moderation/Database/Migrations'));

        // Register routes
        $this->loadRoutesFrom(app_path('Domains/Moderation/Private/routes.php'));

        // Register translations
        $this->loadTranslationsFrom(app_path('Domains/Moderation/Private/Resources/lang'), 'moderation');

        // Register views
        $this->loadViewsFrom(app_path('Domains/Moderation/Private/Resources/views'), 'moderation');

        // Register PHP and anonymous components
        Blade::componentNamespace('App\\Domains\\Moderation\\Private\\View\\Components', 'moderation');
        Blade::anonymousComponentPath(app_path('Domains/Moderation/Private/Resources/views/components'), 'moderation');
    }
}
