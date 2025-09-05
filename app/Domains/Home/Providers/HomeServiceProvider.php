<?php

namespace App\Domains\Home\Providers;

use Illuminate\Support\ServiceProvider;

class HomeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load namespaced views for the Home public domain
        $this->loadViewsFrom(__DIR__ . '/../Views', 'home');

        // Load namespaced language files for the Home public domain
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'home');
    }
}
