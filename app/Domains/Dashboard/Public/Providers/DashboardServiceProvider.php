<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Public\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class DashboardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load translations for the Dashboard domain from Private resources
        $this->loadTranslationsFrom(app_path('Domains/Dashboard/Private/Resources/lang'), 'dashboard');

        // Load namespaced views for the Dashboard domain from Private resources
        $this->loadViewsFrom(app_path('Domains/Dashboard/Private/Resources/views'), 'dashboard');

        // Register Blade component namespace for Dashboard domain
        Blade::componentNamespace('App\\Domains\\Dashboard\\Private\\View\\Components', 'dashboard');

        // Load domain routes from Private
        $this->loadRoutesFrom(app_path('Domains/Dashboard/Private/routes.php'));
    }
}
