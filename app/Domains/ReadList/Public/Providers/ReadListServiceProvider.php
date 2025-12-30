<?php

namespace App\Domains\ReadList\Public\Providers;

use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Settings\Public\Api\SettingsPublicApi;
use App\Domains\Settings\Public\Contracts\SettingsParameterDefinition;
use App\Domains\Settings\Public\Contracts\SettingsSectionDefinition;
use App\Domains\Settings\Public\Contracts\SettingsTabDefinition;
use App\Domains\Shared\Contracts\ParameterType;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\ReadList\Public\Notifications\ReadListAddedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListChapterPublishedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListChapterUnpublishedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListStoryDeletedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListStoryRepublishedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListStoryUnpublishedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListStoryCompletedNotification;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\ReadList\Private\Listeners\HandleStoryDeletedForReadList;
use App\Domains\ReadList\Private\Listeners\HandleStoryVisibilityChangedForReadList;
use App\Domains\ReadList\Private\Listeners\NotifyReadersOnChapterModified;
use App\Domains\ReadList\Private\Listeners\NotifyReadersOnStoryCompleted;
use App\Domains\ReadList\Private\Listeners\HandleUserDeletedForReadList;
use App\Domains\ReadList\Public\Events\StoryAddedToReadList;
use App\Domains\ReadList\Public\Events\StoryRemovedFromReadList;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\ChapterPublished;
use App\Domains\Story\Public\Events\ChapterUnpublished;
use App\Domains\Story\Public\Events\StoryDeleted;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
use App\Domains\Story\Public\Events\StoryUpdated;

class ReadListServiceProvider extends ServiceProvider
{
    public const TAB_READLIST = 'readlist';
    public const SECTION_GENERAL = 'general';
    public const KEY_HIDE_UP_TO_DATE = 'hide-up-to-date';

    public function boot(): void
    {
        // Load domain migrations
        $this->loadMigrationsFrom(app_path('Domains/ReadList/Database/Migrations'));

        // Load routes
        $this->loadRoutesFrom(app_path('Domains/ReadList/Private/routes.php'));

        // Register views under the 'read-list' namespace from Private resources
        $this->loadViewsFrom(app_path('Domains/ReadList/Private/Resources/views'), 'read-list');

        // Register PHP components
        Blade::componentNamespace('App\\Domains\\ReadList\\Private\\View\\Components', 'read-list');

        // Register anonymous components with prefix (<x-read-list::...>)
        Blade::anonymousComponentPath(app_path('Domains/ReadList/Private/Resources/views/components'), 'read-list');

        // Load PHP translations for the ReadList domain under 'readlist::'
        $this->loadTranslationsFrom(app_path('Domains/ReadList/Private/Resources/lang'), 'readlist');

        // Register notification content types
        $factory = app(NotificationFactory::class);
        $factory->register(
            type: ReadListAddedNotification::type(),
            class: ReadListAddedNotification::class
        );
        $factory->register(
            type: ReadListChapterPublishedNotification::type(),
            class: ReadListChapterPublishedNotification::class
        );
        $factory->register(
            type: ReadListChapterUnpublishedNotification::type(),
            class: ReadListChapterUnpublishedNotification::class
        );
        $factory->register(
            type: ReadListStoryDeletedNotification::type(),
            class: ReadListStoryDeletedNotification::class
        );
        $factory->register(
            type: ReadListStoryUnpublishedNotification::type(),
            class: ReadListStoryUnpublishedNotification::class
        );
        $factory->register(
            type: ReadListStoryRepublishedNotification::type(),
            class: ReadListStoryRepublishedNotification::class
        );
        $factory->register(
            type: ReadListStoryCompletedNotification::type(),
            class: ReadListStoryCompletedNotification::class
        );

        // Register ReadList domain events with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(StoryAddedToReadList::name(), StoryAddedToReadList::class);
        $eventBus->registerEvent(StoryRemovedFromReadList::name(), StoryRemovedFromReadList::class);

        // Subscribe to Story events
        $eventBus->subscribe(ChapterPublished::class, [app(NotifyReadersOnChapterModified::class), 'onChapterPublished']);
        $eventBus->subscribe(ChapterCreated::class, [app(NotifyReadersOnChapterModified::class), 'onChapterCreated']);
        $eventBus->subscribe(ChapterUnpublished::class, [app(NotifyReadersOnChapterModified::class), 'onChapterUnpublished']);
        $eventBus->subscribe(ChapterDeleted::class, [app(NotifyReadersOnChapterModified::class), 'onChapterDeleted']);
        $eventBus->subscribe(StoryDeleted::class, [app(HandleStoryDeletedForReadList::class), 'handle']);
        $eventBus->subscribe(StoryVisibilityChanged::class, [app(HandleStoryVisibilityChangedForReadList::class), 'handle']);
        $eventBus->subscribe(StoryUpdated::class, [app(NotifyReadersOnStoryCompleted::class), 'handle']);

        // Subscribe to Auth events
        $eventBus->subscribe(UserDeleted::class, [app(HandleUserDeletedForReadList::class), 'handle']);

        // Register settings after all providers have booted
        $this->app->booted(function () {
            $this->registerSettings();
        });
    }

    private function registerSettings(): void
    {
        $settingsApi = app(SettingsPublicApi::class);

        // Skip if already registered (idempotent for testing)
        if ($settingsApi->getTab(self::TAB_READLIST) !== null) {
            return;
        }

        // Register "Readlist" tab
        $settingsApi->registerTab(new SettingsTabDefinition(
            id: self::TAB_READLIST,
            order: 20,
            nameKey: 'readlist::settings.tabs.readlist',
            icon: 'book',
        ));

        // Register "General" section
        $settingsApi->registerSection(new SettingsSectionDefinition(
            tabId: self::TAB_READLIST,
            id: self::SECTION_GENERAL,
            order: 10,
            nameKey: 'readlist::settings.sections.general.name',
            descriptionKey: 'readlist::settings.sections.general.description',
        ));

        // Register "Hide up to date" parameter
        $settingsApi->registerParameter(new SettingsParameterDefinition(
            tabId: self::TAB_READLIST,
            sectionId: self::SECTION_GENERAL,
            key: self::KEY_HIDE_UP_TO_DATE,
            type: ParameterType::BOOL,
            default: false,
            order: 10,
            nameKey: 'readlist::settings.params.hide-up-to-date.name',
            descriptionKey: 'readlist::settings.params.hide-up-to-date.description',
        ));
    }
}
