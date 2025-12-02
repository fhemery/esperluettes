<?php

namespace App\Domains\StoryRef\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\StoryRef\Public\Events\StoryRefAdded;
use App\Domains\StoryRef\Public\Events\StoryRefRemoved;
use App\Domains\StoryRef\Public\Events\StoryRefUpdated;
use Illuminate\Support\ServiceProvider;

class StoryServiceRefProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register StoryRef domain services here if needed.
    }

    public function boot(): void
    {
        // Load migrations from the StoryRef domain root
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');

        // Load routes
        $this->loadRoutesFrom(__DIR__ . '/../../Private/routes.php');

        // Load views
        $this->loadViewsFrom(__DIR__ . '/../../Private/Resources/views', 'story_ref');

        // PHP translations (namespaced)
        $this->loadTranslationsFrom(__DIR__ . '/../../Private/Resources/lang', 'story_ref');

        // Register StoryRef domain events mapping with EventBus
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(StoryRefAdded::name(), StoryRefAdded::class);
        $eventBus->registerEvent(StoryRefUpdated::name(), StoryRefUpdated::class);
        $eventBus->registerEvent(StoryRefRemoved::name(), StoryRefRemoved::class);

        $this->registerAdminNavigation();
    }

    protected function registerAdminNavigation(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        // Register audience admin page (replaces Filament resource)
        $registry->registerPage(
            'story_ref.audiences',
            'story',
            __('story_ref::admin.audiences.nav_label'),
            AdminRegistryTarget::route('story_ref.admin.audiences.index'),
            'group',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        // Register copyright admin page (replaces Filament resource)
        $registry->registerPage(
            'story_ref.copyrights',
            'story',
            __('story_ref::admin.copyrights.nav_label'),
            AdminRegistryTarget::route('story_ref.admin.copyrights.index'),
            'copyright',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            2,
        );

        // Register feedback admin page (replaces Filament resource)
        $registry->registerPage(
            'story_ref.feedbacks',
            'story',
            __('story_ref::admin.feedbacks.nav_label'),
            AdminRegistryTarget::route('story_ref.admin.feedbacks.index'),
            'forum',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            3,
        );

        // Register genre admin page (replaces Filament resource)
        $registry->registerPage(
            'story_ref.genres',
            'story',
            __('story_ref::admin.genres.nav_label'),
            AdminRegistryTarget::route('story_ref.admin.genres.index'),
            'menu_book',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            4,
        );
    }
}
