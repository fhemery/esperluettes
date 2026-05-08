<?php

declare(strict_types=1);

namespace App\Domains\Administration\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use Closure;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AdministrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AdminNavigationRegistry::class);
    }

    public function boot(): void
    {
        // Register routes
        $this->loadRoutesFrom(app_path('Domains/Administration/Private/routes.php'));
        // Load translations
        $this->loadTranslationsFrom(app_path('Domains/Administration/Private/Resources/lang'), 'administration');
        // Load Administration domain views
        $this->loadViewsFrom(app_path('Domains/Administration/Private/Resources/views'), 'administration');

        Blade::component(
            \App\Domains\Administration\Public\View\LayoutComponent::class,
            'admin::layout'
        );

        $this->registerAdminPages();
        $this->addLegacyAdminLinks();
    }

    protected function registerAdminPages(): void
    {
        $registry = app(AdminNavigationRegistry::class);
        $registry->registerGroup('administration', 'administration::admin.category.label', 10);

        // Dashboard (/administration) is not registered here, it is
        // hardcoded in the sidebar

        $registry->registerPage(
            __('administration::maintenance.key'),
            'administration',
            'administration::maintenance.title',
            AdminRegistryTarget::route('administration.maintenance'),
            'settings',
            [Roles::TECH_ADMIN],
            1,
        );

        $registry->registerPage(
            'logs',
            'administration',
            'administration::logs.title',
            AdminRegistryTarget::route('administration.logs'),
            'description',
            [Roles::TECH_ADMIN],
            2,
        );
    }

    protected function addLegacyAdminLinks(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerGroup('calendar', 'calendar::admin.nav_group', 20);
        $registry->registerGroup('config', 'config::admin.nav_group', 30);
        $registry->registerGroup('moderation', 'moderation::admin.nav_group', 40);
        $registry->registerGroup('story', 'story_ref::admin.nav_group', 70);
        $registry->registerGroup('auth', 'auth::admin.users.title', 80);
        $registry->registerGroup('faq', 'faq::admin.nav_group', 90);
        $registry->registerGroup('events', 'events::admin.domain_events.nav_group', 100);

        // Auth resources
        $registry->registerPage(
            'auth.users',
            'auth',
            'auth::admin.users.nav_label',
            AdminRegistryTarget::route('auth.admin.users.index'),
            'groups',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        $registry->registerPage(
            'auth.roles',
            'auth',
            'auth::admin.roles.nav_label',
            AdminRegistryTarget::url('/admin/roles'),
            'admin_panel_settings',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            2,
        );

        $registry->registerPage(
            'auth.activation_codes',
            'auth',
            'auth::admin.activation_codes.nav_label',
            AdminRegistryTarget::url('/admin/auth/activation-codes'),
            'key',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            3,
        );

        // Calendar
        $registry->registerPage(
            'calendar.activities',
            'calendar',
            'calendar::admin.activities.nav_label',
            AdminRegistryTarget::url('/admin/calendar/activities'),
            'calendar_month',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        // Events — now registered in EventsServiceProvider (custom admin page)

        // FAQ — now registered in FaqServiceProvider (custom admin pages)

        // Moderation — reasons and reports now registered in ModerationServiceProvider (custom admin pages)

        // Story references
        // NOTE: All StoryRef admin pages are now registered in StoryServiceRefProvider (custom admin pages)
    }
}
