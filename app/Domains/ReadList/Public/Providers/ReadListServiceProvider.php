<?php

namespace App\Domains\ReadList\Public\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\ReadList\Public\Notifications\ReadListAddedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListChapterPublishedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListChapterUnpublishedNotification;
use App\Domains\ReadList\Public\Notifications\ReadListStoryDeletedNotification;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\ReadList\Private\Listeners\HandleStoryDeletedForReadList;
use App\Domains\ReadList\Private\Listeners\NotifyReadersOnChapterModified;
use App\Domains\ReadList\Private\Listeners\NotifyReadersOnChapterPublished;
use App\Domains\ReadList\Private\Listeners\NotifyReadersOnStoryDeleted;
use App\Domains\ReadList\Public\Events\StoryAddedToReadList;
use App\Domains\ReadList\Public\Events\StoryRemovedFromReadList;

class ReadListServiceProvider extends ServiceProvider
{
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

        // Register ReadList domain events with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(StoryAddedToReadList::name(), StoryAddedToReadList::class);
        $eventBus->registerEvent(StoryRemovedFromReadList::name(), StoryRemovedFromReadList::class);

        // Subscribe to Story events for RL-008 (chapter publication notifications)
        $eventBus->subscribe(\App\Domains\Story\Public\Events\ChapterPublished::class, [app(NotifyReadersOnChapterModified::class), 'onChapterPublished']);
        $eventBus->subscribe(\App\Domains\Story\Public\Events\ChapterCreated::class, [app(NotifyReadersOnChapterModified::class), 'onChapterCreated']);
        $eventBus->subscribe(\App\Domains\Story\Public\Events\ChapterUnpublished::class, [app(NotifyReadersOnChapterModified::class), 'onChapterUnpublished']);
        $eventBus->subscribe(\App\Domains\Story\Public\Events\ChapterDeleted::class, [app(NotifyReadersOnChapterModified::class), 'onChapterDeleted']);
        $eventBus->subscribe(\App\Domains\Story\Public\Events\StoryDeleted::class, [app(HandleStoryDeletedForReadList::class), 'handle']);
    }
}
