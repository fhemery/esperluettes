<?php

namespace App\Domains\Profile\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ProfileServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register any bindings or singletons here if needed
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register language files
        $this->loadJsonTranslationsFrom(
            __DIR__.'/../Resources/lang',
            'fr'
        );
        
        // Register view namespace for Profile domain
        View::addNamespace('profile', app_path('Domains/Profile/Views'));
    }
}
