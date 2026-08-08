<?php

namespace App\Domains\News\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\News\Private\Listeners\NotifyOnNewsComment;
use App\Domains\News\Private\Listeners\RemoveCreatorOnUserDeleted;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Services\NewsCommentPolicy;
use App\Domains\News\Private\Observers\NewsObserver;
use App\Domains\News\Public\Events\NewsDeleted;
use App\Domains\News\Public\Events\NewsPublished;
use App\Domains\News\Public\Events\NewsUnpublished;
use App\Domains\News\Public\Events\NewsUpdated;
use App\Domains\News\Public\Notifications\NewsPublishedNotification;
use App\Domains\News\Public\Notifications\NewsReplyCommentNotification;
use App\Domains\Notification\Public\Services\NotificationFactory;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class NewsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load PHP namespaced translations for the News domain
        $this->loadTranslationsFrom(app_path('Domains/News/Private/Resources/lang'), 'news');

        // Register view namespace for the News domain (Private resources)
        View::addNamespace('news', app_path('Domains/News/Private/Resources/views'));

        // Register Blade component namespace for News domain
        Blade::componentNamespace('App\\Domains\\News\\Private\\View\\Components', 'news');

        // Load migrations
        $this->loadMigrationsFrom(app_path('Domains/News/Database/migrations'));

        // Load routes
        $this->loadRoutesFrom(app_path('Domains/News/Private/routes.php'));

        // Model observers
        News::observe(NewsObserver::class);

        // Let Media GC know which image files News still uses (header + block images).
        app(\App\Domains\Media\Public\Contracts\MediaUsageRegistry::class)
            ->register(new \App\Domains\News\Private\Support\NewsMediaUsageProvider());

        // Register News domain events mapping with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(NewsPublished::name(), NewsPublished::class);
        $eventBus->registerEvent(NewsUpdated::name(), NewsUpdated::class);
        $eventBus->registerEvent(NewsDeleted::name(), NewsDeleted::class);
        $eventBus->registerEvent(NewsUnpublished::name(), NewsUnpublished::class);
        
        // Subscribe to user deletion to nullify creator id on news
        $eventBus->subscribe(UserDeleted::name(), [RemoveCreatorOnUserDeleted::class, 'handle']);

        // Comments on news articles: published-only, 20-char minimum on root comments
        app(CommentPolicyRegistry::class)->register('news', app(NewsCommentPolicy::class));

        // Notify thread participants when someone replies on a news comment thread
        $eventBus->subscribe(CommentPosted::class, [app(NotifyOnNewsComment::class), 'handle']);

        // Register notification types
        $notificationFactory = app(NotificationFactory::class);

        // News-specific group: must be registered before any of its types
        $notificationFactory->registerGroup('news-comments', 45, 'news::notification.settings.group_comments');
        $notificationFactory->register(
            type: NewsReplyCommentNotification::type(),
            class: NewsReplyCommentNotification::class,
            groupId: 'news-comments',
            nameKey: 'news::notification.settings.type_reply_comment',
        );

        $notificationFactory->register(
            type: NewsPublishedNotification::type(),
            class: NewsPublishedNotification::class,
            groupId: 'news',
            nameKey: 'news::notification.settings.type_news_published',
        );

        $this->registerAdminNavigation();
    }

    protected function registerAdminNavigation(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerGroup('news', 'news::admin.nav.group', 50);

        // Register News management page
        $registry->registerPage(
            'news.management',
            'news',
            'news::admin.nav.news',
            AdminRegistryTarget::route('news.admin.index'),
            'newspaper',
            [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN],
            10,
        );

        // Register Pinned news (carousel) ordering page
        $registry->registerPage(
            'news.pinned',
            'news',
            'news::admin.nav.pinned',
            AdminRegistryTarget::route('news.admin.pinned.index'),
            'push_pin',
            [Roles::MODERATOR, Roles::ADMIN, Roles::TECH_ADMIN],
            11,
        );
    }
}
