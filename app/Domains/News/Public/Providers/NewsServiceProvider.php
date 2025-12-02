<?php

namespace App\Domains\News\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\News\Private\Listeners\RemoveCreatorOnUserDeleted;
use App\Domains\News\Private\Models\News;
use App\Domains\News\Private\Observers\NewsObserver;
use App\Domains\News\Public\Events\NewsDeleted;
use App\Domains\News\Public\Events\NewsPublished;
use App\Domains\News\Public\Events\NewsUnpublished;
use App\Domains\News\Public\Events\NewsUpdated;
use App\Domains\News\Public\Notifications\NewsPublishedNotification;
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

        // Register News domain events mapping with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(NewsPublished::name(), NewsPublished::class);
        $eventBus->registerEvent(NewsUpdated::name(), NewsUpdated::class);
        $eventBus->registerEvent(NewsDeleted::name(), NewsDeleted::class);
        $eventBus->registerEvent(NewsUnpublished::name(), NewsUnpublished::class);
        
        // Subscribe to user deletion to nullify creator id on news
        $eventBus->subscribe(UserDeleted::name(), [RemoveCreatorOnUserDeleted::class, 'handle']);

        // Register notification types
        app(NotificationFactory::class)->register(
            NewsPublishedNotification::type(),
            NewsPublishedNotification::class
        );

        $this->registerAdminNavigation();
    }

    protected function registerAdminNavigation(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerGroup('news', __('news::admin.nav.group'), 50);

        // Register News management page
        $registry->registerPage(
            'news.management',
            'news',
            __('news::admin.nav.news'),
            AdminRegistryTarget::route('news.admin.index'),
            'newspaper',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            10,
        );

        // Register Pinned news (carousel) ordering page
        $registry->registerPage(
            'news.pinned',
            'news',
            __('news::admin.nav.pinned'),
            AdminRegistryTarget::route('news.admin.pinned.index'),
            'push_pin',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            11,
        );
    }
}
