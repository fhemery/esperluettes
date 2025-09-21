<?php

declare(strict_types=1);

namespace App\Domains\Home\Public\Providers;

use Illuminate\Support\ServiceProvider;

class HomeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load namespaced views for the Home domain
        $this->loadViewsFrom(app_path('Domains/Home/Private/Resources/views'), 'home');

        // Load namespaced language files for the Home domain
        $this->loadTranslationsFrom(app_path('Domains/Home/Private/Resources/lang'), 'home');

        // Load domain routes
        $this->loadRoutesFrom(app_path('Domains/Home/Private/routes.php'));
    }
}
