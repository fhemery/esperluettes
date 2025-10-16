<?php

namespace App\Domains\Profile\Public\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use App\Domains\Profile\Private\Listeners\CreateProfileOnUserRegistered;
use App\Domains\Profile\Private\Listeners\ClearProfileCacheOnEmailVerified;
use App\Domains\Shared\Contracts\ProfilePublicApi as ProfilePublicApiContract;
use App\Domains\Profile\Private\Api\ProfileApi;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Auth\Public\Events\EmailVerified;
use App\Domains\Profile\Public\Events\ProfileDisplayNameChanged;
use App\Domains\Profile\Public\Events\AvatarChanged;
use App\Domains\Profile\Public\Events\BioUpdated;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Profile\Private\Listeners\RemoveProfileOnUserDeleted;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use App\Domains\Profile\Public\Events\AvatarModerated;
use App\Domains\Profile\Public\Moderation\ProfileSnapshotFormatter;

class ProfileServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Bind Shared contract to Profile implementation
        $this->app->singleton(ProfilePublicApiContract::class, ProfileApi::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register domain-specific migrations
        $this->loadMigrationsFrom(app_path('Domains/Profile/Database/Migrations'));

        // Register domain routes
        $this->loadRoutesFrom(app_path('Domains/Profile/Private/routes.php'));

        // Register translations
        // Register JSON language files (domain-level)
        $this->loadJsonTranslationsFrom(
            app_path('Domains/Profile/Private/Resources/lang')
        );
        // Register PHP translations (namespaced)
        $this->loadTranslationsFrom(app_path('Domains/Profile/Private/Resources/lang'), 'profile');
        
        // Register view namespace for Profile domain
        View::addNamespace('profile', app_path('Domains/Profile/Private/Resources/views'));

        // Ensure Carbon uses the current app locale (for translated month/day names)
        Carbon::setLocale(app()->getLocale());

        $eventBus = app(EventBus::class);

        // Subscribe to domain event via EventBus
        $eventBus->subscribe(UserRegistered::name(), [CreateProfileOnUserRegistered::class, 'handle']);
        $eventBus->subscribe(EmailVerified::name(), [ClearProfileCacheOnEmailVerified::class, 'handle']);
        // Clean up profile on user deletion
        $eventBus->subscribe(UserDeleted::name(), [RemoveProfileOnUserDeleted::class, 'handle']);

        // Register Profile domain events mapping
        $eventBus->registerEvent(ProfileDisplayNameChanged::name(), ProfileDisplayNameChanged::class);
        $eventBus->registerEvent(AvatarChanged::name(), AvatarChanged::class);
        $eventBus->registerEvent(BioUpdated::name(), BioUpdated::class);
        $eventBus->registerEvent(AvatarModerated::name(), AvatarModerated::class);

        // Register Profile topic for moderation
        app(ModerationRegistry::class)->register(
            key: 'profile',
            displayName: __('profile::moderation.topic_name'),
            formatterClass: ProfileSnapshotFormatter::class
        );
    }
}
