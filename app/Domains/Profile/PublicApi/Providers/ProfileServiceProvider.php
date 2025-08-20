<?php

namespace App\Domains\Profile\PublicApi\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Event;
use App\Domains\Auth\Events\UserRegistered;
use App\Domains\Profile\Listeners\CreateProfileOnUserRegistered;
use App\Domains\Shared\Contracts\ProfilePublicApi as ProfilePublicApiContract;
use App\Domains\Profile\PublicApi\ProfilePublicApi;

class ProfileServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Shared contract to Profile implementation
        $this->app->singleton(ProfilePublicApiContract::class, ProfilePublicApi::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register domain-specific migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');

        // Register JSON language files (domain-level)
        $this->loadJsonTranslationsFrom(
            __DIR__.'/../../Resources/lang'
        );
        
        // Register PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../../Resources/lang', 'profile');
        
        // Register view namespace for Profile domain
        View::addNamespace('profile', app_path('Domains/Profile/Views'));

        // Ensure Carbon uses the current app locale (for translated month/day names)
        Carbon::setLocale(app()->getLocale());

        Event::listen(
            UserRegistered::class,
            [CreateProfileOnUserRegistered::class, 'handle']
        );
    }
}
