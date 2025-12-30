<?php

namespace App\Domains\Profile\Public\Providers;

use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use App\Domains\Shared\Contracts\ParameterType;
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
use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Profile\Private\Listeners\SoftDeleteProfileOnUserDeactivated;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Profile\Private\Listeners\RestoreProfileOnUserReactivated;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use App\Domains\Profile\Public\Events\AvatarModerated;
use App\Domains\Profile\Public\Events\AboutModerated;
use App\Domains\Profile\Public\Events\SocialModerated;
use App\Domains\Profile\Private\Support\Moderation\ProfileSnapshotFormatter;

class ProfileServiceProvider extends ServiceProvider
{
    public const TAB_PROFILE = 'profile';
    public const SECTION_PRIVACY = 'privacy';
    public const KEY_HIDE_COMMENTS_SECTION = 'hide-comments-section';

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
        $eventBus->subscribe(UserDeleted::name(), [RemoveProfileOnUserDeleted::class, 'handle']);
        $eventBus->subscribe(UserDeactivated::name(), [SoftDeleteProfileOnUserDeactivated::class, 'handle']);
        $eventBus->subscribe(UserReactivated::name(), [RestoreProfileOnUserReactivated::class, 'handle']);

        // Register Profile domain events mapping
        $eventBus->registerEvent(ProfileDisplayNameChanged::name(), ProfileDisplayNameChanged::class);
        $eventBus->registerEvent(AvatarChanged::name(), AvatarChanged::class);
        $eventBus->registerEvent(BioUpdated::name(), BioUpdated::class);
        $eventBus->registerEvent(AvatarModerated::name(), AvatarModerated::class);
        $eventBus->registerEvent(AboutModerated::name(), AboutModerated::class);
        $eventBus->registerEvent(SocialModerated::name(), SocialModerated::class);

        // Register Profile topic for moderation
        app(ModerationRegistry::class)->register(
            key: 'profile',
            displayName: __('profile::moderation.topic_name'),
            formatterClass: ProfileSnapshotFormatter::class
        );

        // Register settings after all providers have booted
        $this->app->booted(function () {
            $this->registerSettings();
        });
    }

    private function registerSettings(): void
    {
        $settingsApi = app(SettingsPublicApi::class);

        // Skip if already registered (idempotent for testing)
        if ($settingsApi->getTab(self::TAB_PROFILE) !== null) {
            return;
        }

        // Register "Profile" tab
        $settingsApi->registerTab(new SettingsTabDefinition(
            id: self::TAB_PROFILE,
            order: 30,
            nameKey: 'profile::settings.tabs.profile',
            icon: 'face',
        ));

        // Register "Privacy" section
        $settingsApi->registerSection(new SettingsSectionDefinition(
            tabId: self::TAB_PROFILE,
            id: self::SECTION_PRIVACY,
            order: 10,
            nameKey: 'profile::settings.sections.privacy.name',
            descriptionKey: 'profile::settings.sections.privacy.description',
        ));

        // Register "Hide comments section" parameter
        $settingsApi->registerParameter(new SettingsParameterDefinition(
            tabId: self::TAB_PROFILE,
            sectionId: self::SECTION_PRIVACY,
            key: self::KEY_HIDE_COMMENTS_SECTION,
            type: ParameterType::BOOL,
            default: false,
            order: 10,
            nameKey: 'profile::settings.params.hide-comments-section.name',
            descriptionKey: 'profile::settings.params.hide-comments-section.description',
        ));
    }
}
