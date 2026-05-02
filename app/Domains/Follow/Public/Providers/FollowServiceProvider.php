<?php

namespace App\Domains\Follow\Public\Providers;

use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Follow\Private\Listeners\StoryCreatedListener;
use App\Domains\Follow\Private\Listeners\StoryVisibilityChangedListener;
use App\Domains\Follow\Private\Notifications\NewFollowerNotification;
use App\Domains\Follow\Private\Notifications\NewStoryNotification;
use App\Domains\Follow\Public\Events\UserFollowed;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use App\Domains\Story\Public\Events\StoryCreated;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class FollowServiceProvider extends ServiceProvider
{
    public const KEY_HIDE_FOLLOWING_TAB = 'hide-following-tab';
    public const TAB_PROFILE = 'profile';
    public const SECTION_PRIVACY = 'privacy';

    public function register(): void {}

    public function boot(): void
    {
        $this->loadMigrationsFrom(app_path('Domains/Follow/Database/Migrations'));
        $this->loadRoutesFrom(app_path('Domains/Follow/Private/routes.php'));
        $this->loadTranslationsFrom(app_path('Domains/Follow/Private/Resources/lang'), 'follow');

        $this->loadViewsFrom(app_path('Domains/Follow/Private/Resources/views'), 'follow');
        Blade::componentNamespace('App\\Domains\\Follow\\Private\\Views\\Components', 'follow');

        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(UserFollowed::name(), UserFollowed::class);
        $eventBus->subscribe(StoryCreated::name(), [StoryCreatedListener::class, 'handle']);
        $eventBus->subscribe(StoryVisibilityChanged::name(), [StoryVisibilityChangedListener::class, 'handle']);

        $this->app->booted(function () {
            $this->registerNotifications();
            $this->registerSettings();
        });
    }

    private function registerNotifications(): void
    {
        $factory = app(NotificationFactory::class);

        $factory->registerGroup(
            id: 'follow',
            sortOrder: 50,
            translationKey: 'follow::notification.groups.follow',
        );

        $factory->register(
            type: NewFollowerNotification::type(),
            class: NewFollowerNotification::class,
            groupId: 'follow',
            nameKey: 'follow::notification.settings.type_new_follower',
        );

        $factory->register(
            type: NewStoryNotification::type(),
            class: NewStoryNotification::class,
            groupId: 'follow',
            nameKey: 'follow::notification.settings.type_new_story',
        );
    }

    private function registerSettings(): void
    {
        $settingsApi = app(SettingsPublicApi::class);

        if ($settingsApi->getParameter(self::TAB_PROFILE, self::KEY_HIDE_FOLLOWING_TAB) !== null) {
            return;
        }

        $settingsApi->registerParameter(new SettingsParameterDefinition(
            tabId: self::TAB_PROFILE,
            sectionId: self::SECTION_PRIVACY,
            key: self::KEY_HIDE_FOLLOWING_TAB,
            type: ParameterType::BOOL,
            default: false,
            order: 20,
            nameKey: 'follow::follow.settings.params.hide-following-tab.name',
            descriptionKey: 'follow::follow.settings.params.hide-following-tab.description',
        ));
    }
}
