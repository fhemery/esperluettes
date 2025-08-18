<?php

namespace App\Domains\Profile\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use App\Domains\Auth\Events\UserNameUpdated;
use App\Domains\Profile\Listeners\SyncProfileNameAndSlug;
use App\Domains\Auth\Events\UserRegistered;
use App\Domains\Profile\Listeners\CreateProfileOnUserRegistered;

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
        
        // Register PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'profile');
        
        // Register view namespace for Profile domain
        View::addNamespace('profile', app_path('Domains/Profile/Views'));

        // Ensure Carbon uses the current app locale (for translated month/day names)
        Carbon::setLocale(app()->getLocale());

        // Register domain event listeners (Profile listens to Auth events)
        Event::listen(
            UserNameUpdated::class,
            [SyncProfileNameAndSlug::class, 'handle']
        );

        Event::listen(
            UserRegistered::class,
            [CreateProfileOnUserRegistered::class, 'handle']
        );
    }
}
