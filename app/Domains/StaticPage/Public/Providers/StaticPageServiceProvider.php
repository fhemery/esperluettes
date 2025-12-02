<?php

namespace App\Domains\StaticPage\Public\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Domains\StaticPage\Private\Models\StaticPage;
use App\Domains\StaticPage\Private\Observers\StaticPageObserver;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StaticPage\Public\Events\StaticPagePublished;
use App\Domains\StaticPage\Public\Events\StaticPageUnpublished;
use App\Domains\StaticPage\Public\Events\StaticPageUpdated;
use App\Domains\StaticPage\Public\Events\StaticPageDeleted;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StaticPage\Private\Listeners\RemoveCreatorOnUserDeleted;
use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;

class StaticPageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load translations from Private resources
        $this->loadTranslationsFrom(app_path('Domains/StaticPage/Private/Resources/lang'), 'static');

        // Register view namespace pointing to Private resources
        View::addNamespace('static', app_path('Domains/StaticPage/Private/Resources/views'));

        // Load migrations
        $this->loadMigrationsFrom(app_path('Domains/StaticPage/Database/migrations'));

        // Load routes (catch-all must be registered late; provider order in bootstrap/providers.php ensures this runs after others)
        $this->loadRoutesFrom(app_path('Domains/StaticPage/Private/routes.php'));

        // Model observers
        StaticPage::observe(StaticPageObserver::class);

        // Register StaticPage domain events mapping with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(StaticPagePublished::name(), StaticPagePublished::class);
        $eventBus->registerEvent(StaticPageUnpublished::name(), StaticPageUnpublished::class);
        $eventBus->registerEvent(StaticPageUpdated::name(), StaticPageUpdated::class);
        $eventBus->registerEvent(StaticPageDeleted::name(), StaticPageDeleted::class);
        // Subscribe to user deletion to nullify creator id
        $eventBus->subscribe(UserDeleted::name(), [RemoveCreatorOnUserDeleted::class, 'handle']);

        // Register admin navigation
        $this->registerAdminPages();
    }

    private function registerAdminPages(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerGroup('static', __('static::admin.nav.group'), 60);

        $registry->registerPage(
            'static.pages',
            'static',
            __('static::admin.nav.pages'),
            AdminRegistryTarget::route('static.admin.index'),
            'description',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );
    }
}
