<?php

namespace App\Domains\Story\Public\Providers;

use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Observers\ChapterObserver;
use App\Domains\Story\Private\Policies\StoryPolicy;
use App\Domains\Story\Private\Policies\ChapterPolicy;
use App\Domains\Story\Private\Services\ChapterCommentPolicy;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Story\Public\Events\StoryCreated;
use App\Domains\Story\Public\Events\StoryUpdated;
use App\Domains\Story\Public\Events\StoryDeleted;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\ChapterPublished;
use App\Domains\Story\Public\Events\ChapterUnpublished;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
use App\Domains\Story\Public\Events\StoryModeratedAsPrivate;
use App\Domains\Story\Public\Events\StorySummaryModerated;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Story\Private\Listeners\GrantInitialCreditsOnUserRegistered;
use App\Domains\Story\Private\Listeners\GrantCreditOnRootCommentPosted;
use App\Domains\Story\Private\Listeners\MarkChapterReadOnRootCommentPosted;
use App\Domains\Story\Private\Listeners\NotifyOnChapterComment;
use App\Domains\Story\Private\Listeners\DecreaseCreditsOnCommentDeletedListener;
use App\Domains\Story\Private\Listeners\RemoveStoriesOnUserDeleted;
use App\Domains\Story\Private\Listeners\RemoveChapterCreditsOnUserDeleted;
use App\Domains\Auth\Public\Events\UserDeactivated;
use App\Domains\Story\Private\Listeners\SoftDeleteStoriesOnUserDeactivated;
use App\Domains\Auth\Public\Events\UserReactivated;
use App\Domains\Comment\Public\Events\CommentDeletedByModeration;
use App\Domains\Story\Private\Listeners\RestoreStoriesOnUserReactivated;
use App\Domains\Moderation\Public\Services\ModerationRegistry;
use App\Domains\Story\Public\Events\ChapterContentModerated;
use App\Domains\Story\Public\Events\ChapterUnpublishedByModeration;
use App\Domains\Story\Public\Events\ChapterCommentNotificationsBackfilled;
use App\Domains\Story\Public\Events\StoryExcludedFromEvents;
use App\Domains\Story\Private\Support\Moderation\StorySnapshotFormatter;
use App\Domains\Story\Private\Support\Moderation\ChapterSnapshotFormatter;
use App\Domains\Notification\Public\Services\NotificationFactory;
use App\Domains\Story\Public\Notifications\ChapterCommentNotification;
use App\Domains\Story\Public\Notifications\CoAuthorChapterCreatedNotification;
use App\Domains\Story\Public\Notifications\CoAuthorChapterUpdatedNotification;
use App\Domains\Story\Public\Notifications\CoAuthorChapterDeletedNotification;
use App\Domains\Story\Public\Notifications\CollaboratorRoleGivenNotification;
use App\Domains\Story\Public\Notifications\CollaboratorRemovedNotification;
use App\Domains\Story\Public\Notifications\CollaboratorLeftNotification;
use App\Domains\Story\Private\Console\BackfillChapterCommentNotificationsCommand;

class StoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                BackfillChapterCommentNotificationsCommand::class,
            ]);
        }
        
        // Load domain migrations
        $this->loadMigrationsFrom(app_path('Domains/Story/Database/Migrations'));

        // Load routes
        $this->loadRoutesFrom(app_path('Domains/Story/Private/routes.php'));

        // Register views under the 'story' namespace from Private resources
        $this->loadViewsFrom(app_path('Domains/Story/Private/Resources/views'), 'story');

        // Register PHP components
        Blade::componentNamespace('App\\Domains\\Story\\Private\\View\\Components', 'story');
        
        
        // Register anonymous components, both unprefixed and prefixed (<x-story::...>)
        Blade::anonymousComponentPath(app_path('Domains/Story/Private/Resources/views/components'), 'story');

        // Load PHP translations for the Story domain under 'story::'
        $this->loadTranslationsFrom(app_path('Domains/Story/Private/Resources/lang'), 'story');

        // Register policies
        Gate::policy(Story::class, StoryPolicy::class);
        Gate::policy(Chapter::class, ChapterPolicy::class);

        // Register model observers
        Chapter::observe(ChapterObserver::class);

        // Register policies
        $registry = app(CommentPolicyRegistry::class);
        $policy = app(ChapterCommentPolicy::class);
        $registry->register('chapter', $policy);

        // Register Story domain events mapping
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(StoryCreated::name(), StoryCreated::class);
        $eventBus->registerEvent(StoryUpdated::name(), StoryUpdated::class);
        $eventBus->registerEvent(StoryDeleted::name(), StoryDeleted::class);
        $eventBus->registerEvent(ChapterCreated::name(), ChapterCreated::class);
        $eventBus->registerEvent(ChapterUpdated::name(), ChapterUpdated::class);
        $eventBus->registerEvent(ChapterPublished::name(), ChapterPublished::class);
        $eventBus->registerEvent(ChapterUnpublished::name(), ChapterUnpublished::class);
        $eventBus->registerEvent(ChapterDeleted::name(), ChapterDeleted::class);
        $eventBus->registerEvent(StoryVisibilityChanged::name(), StoryVisibilityChanged::class);
        $eventBus->registerEvent(StoryModeratedAsPrivate::name(), StoryModeratedAsPrivate::class);
        $eventBus->registerEvent(StorySummaryModerated::name(), StorySummaryModerated::class);
        $eventBus->registerEvent(ChapterUnpublishedByModeration::name(), ChapterUnpublishedByModeration::class);
        $eventBus->registerEvent(ChapterContentModerated::name(), ChapterContentModerated::class);
        $eventBus->registerEvent(ChapterCommentNotificationsBackfilled::name(), ChapterCommentNotificationsBackfilled::class);
        $eventBus->registerEvent(StoryExcludedFromEvents::name(), StoryExcludedFromEvents::class);

        // Subscribe to cross-domain events (after-commit listeners)
        $eventBus->subscribe(UserRegistered::class, [app(GrantInitialCreditsOnUserRegistered::class), 'handle']);
        $eventBus->subscribe(CommentPosted::class, [app(GrantCreditOnRootCommentPosted::class), 'handle']);
        $eventBus->subscribe(CommentPosted::class, [app(MarkChapterReadOnRootCommentPosted::class), 'handle']);
        $eventBus->subscribe(CommentPosted::class, [app(NotifyOnChapterComment::class), 'handle']);
        $eventBus->subscribe(UserDeleted::class, [app(RemoveStoriesOnUserDeleted::class), 'handle']);
        $eventBus->subscribe(UserDeleted::class, [app(RemoveChapterCreditsOnUserDeleted::class), 'handle']);
        $eventBus->subscribe(UserDeactivated::class, [app(SoftDeleteStoriesOnUserDeactivated::class), 'handle']);
        $eventBus->subscribe(UserReactivated::class, [app(RestoreStoriesOnUserReactivated::class), 'handle']);
        $eventBus->subscribe(CommentDeletedByModeration::class, [app(DecreaseCreditsOnCommentDeletedListener::class), 'handle']);

        // Register Story and Chapter topics for moderation
        $moderationRegistry = app(ModerationRegistry::class);
        $moderationRegistry->register(
            key: 'story',
            displayName: __('story::moderation.topic_story'),
            formatterClass: StorySnapshotFormatter::class
        );
        $moderationRegistry->register(
            key: 'chapter',
            displayName: __('story::moderation.topic_chapter'),
            formatterClass: ChapterSnapshotFormatter::class
        );

        // Register notification content types
        $notificationFactory = app(NotificationFactory::class);
        $notificationFactory->register(
            type: ChapterCommentNotification::type(),
            class: ChapterCommentNotification::class
        );
        $notificationFactory->register(
            type: CoAuthorChapterCreatedNotification::type(),
            class: CoAuthorChapterCreatedNotification::class
        );
        $notificationFactory->register(
            type: CoAuthorChapterUpdatedNotification::type(),
            class: CoAuthorChapterUpdatedNotification::class
        );
        $notificationFactory->register(
            type: CoAuthorChapterDeletedNotification::type(),
            class: CoAuthorChapterDeletedNotification::class
        );
        $notificationFactory->register(
            type: CollaboratorRoleGivenNotification::type(),
            class: CollaboratorRoleGivenNotification::class
        );
        $notificationFactory->register(
            type: CollaboratorRemovedNotification::type(),
            class: CollaboratorRemovedNotification::class
        );
        $notificationFactory->register(
            type: CollaboratorLeftNotification::type(),
            class: CollaboratorLeftNotification::class
        );
    }
}
