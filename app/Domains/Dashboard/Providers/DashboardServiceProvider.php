<?php

namespace App\Domains\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load namespaced views for the Dashboard domain
        $this->loadViewsFrom(__DIR__ . '/../Views', 'dashboard');
    }
}
