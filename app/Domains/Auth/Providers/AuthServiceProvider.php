<?php

namespace App\Domains\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Auth\PublicApi\UserPublicApi;
use App\Domains\Auth\PublicApi\UserPublicApiService;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Auth public API
        $this->app->bind(UserPublicApi::class, UserPublicApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register domain-specific migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        
        // Register domain-specific factories
        $this->loadFactoriesFrom(__DIR__ . '/../Database/Factories');
        
        // Register language files
        $this->loadJsonTranslationsFrom(
            __DIR__.'/../Resources/lang',
            'fr'
        );

        // Register PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'auth');

        // Register auth views namespace
        \Illuminate\Support\Facades\View::addNamespace('auth', app_path('Domains/Auth/Views'));
    }
}
