<?php

namespace App\Domains\Message\Public\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class MessageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load database migrations
        $this->loadMigrationsFrom(app_path('Domains/Message/Database/Migrations'));

        // Load domain assets
        $this->loadViewsFrom(app_path('Domains/Message/Private/Resources/views'), 'message');
        $this->loadTranslationsFrom(app_path('Domains/Message/Private/Resources/lang'), 'message');

        // Register PHP components
        Blade::componentNamespace('App\\Domains\\Message\\Private\\View\\Components', 'message');
        
        // Load domain routes
        $this->loadRoutesFrom(app_path('Domains/Message/Private/routes.php'));
    }
}
