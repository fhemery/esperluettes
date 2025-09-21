<?php

namespace App\Domains\Auth\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Events\UserRegistered;
use App\Domains\Auth\Events\EmailVerified;
use App\Domains\Auth\Events\PasswordChanged;
use App\Domains\Auth\Events\PasswordResetRequested;
use App\Domains\Auth\Events\UserLoggedIn;
use App\Domains\Auth\Events\UserLoggedOut;
use App\Domains\Auth\Events\UserRoleGranted;
use App\Domains\Auth\Events\UserRoleRevoked;
use App\Domains\Auth\Events\UserDeactivated;
use App\Domains\Auth\Events\UserReactivated;
use App\Domains\Auth\Events\UserDeleted;

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
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(UserRegistered::name(), UserRegistered::class);
        $eventBus->registerEvent(EmailVerified::name(), EmailVerified::class);
        $eventBus->registerEvent(PasswordChanged::name(), PasswordChanged::class);
        $eventBus->registerEvent(PasswordResetRequested::name(), PasswordResetRequested::class);
        $eventBus->registerEvent(UserLoggedIn::name(), UserLoggedIn::class);
        $eventBus->registerEvent(UserLoggedOut::name(), UserLoggedOut::class);
        $eventBus->registerEvent(UserRoleGranted::name(), UserRoleGranted::class);
        $eventBus->registerEvent(UserRoleRevoked::name(), UserRoleRevoked::class);
        $eventBus->registerEvent(UserDeactivated::name(), UserDeactivated::class);
        $eventBus->registerEvent(UserReactivated::name(), UserReactivated::class);
        $eventBus->registerEvent(UserDeleted::name(), UserDeleted::class);
    }
}
