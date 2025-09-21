<?php

declare(strict_types=1);

namespace App\Domains\Dashboard\Public\Providers;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load namespaced views for the Dashboard domain from Private resources
        $this->loadViewsFrom(app_path('Domains/Dashboard/Private/Resources/views'), 'dashboard');

        // Load domain routes from Private
        $this->loadRoutesFrom(app_path('Domains/Dashboard/Private/routes.php'));
    }
}
