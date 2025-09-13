<?php

namespace App\Domains\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Auth\Events\UserRegistered;
use App\Domains\Auth\Events\EmailVerified;

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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register domain-specific migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        
        // Register language files
        $this->loadJsonTranslationsFrom(
            __DIR__.'/../Resources/lang',
            'fr'
        );

        // Register PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'auth');

        // Register auth views namespace
        \Illuminate\Support\Facades\View::addNamespace('auth', app_path('Domains/Auth/Views'));

        // Register Auth domain events mapping with EventBus
        app(EventBus::class)->registerEvent(UserRegistered::name(), UserRegistered::class);
        app(EventBus::class)->registerEvent(EmailVerified::name(), EmailVerified::class);
    }
}
