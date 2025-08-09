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
// Register domain-specific migrations
$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Register JSON language files (domain-level)
        $this->loadJsonTranslationsFrom(
            __DIR__.'/../Resources/lang'
        );
        
        // Register view namespace for Profile domain
        View::addNamespace('profile', app_path('Domains/Profile/Views'));
    }
}
