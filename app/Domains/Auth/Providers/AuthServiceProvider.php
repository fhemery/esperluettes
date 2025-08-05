<?php

namespace App\Domains\Auth\Providers;

use Illuminate\Support\ServiceProvider;

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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register language files
        $this->loadJsonTranslationsFrom(
            __DIR__.'/../resources/lang',
            'fr'
        );

        // Register auth views namespace
        \Illuminate\Support\Facades\View::addNamespace('auth', app_path('Domains/Auth/Views'));
    }
}
