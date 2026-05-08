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
        $techDomain = __('administration::admin.category.label');
        $registry->registerGroup($techDomain, $techDomain, 10);

        // Dashboard (/administration) is not registered here, it is 
        // hardcoded in the sidebar

        $registry->registerPage(
            __('administration::maintenance.key'),
            $techDomain,
            __('administration::maintenance.title'),
            AdminRegistryTarget::route('administration.maintenance'),
            'settings',
            [Roles::TECH_ADMIN],
            1,
        );

        $registry->registerPage(
            'logs',
            $techDomain,
            __('administration::logs.title'),
            AdminRegistryTarget::route('administration.logs'),
            'description',
            [Roles::TECH_ADMIN],
            2,
        );   
    }

    protected function addLegacyAdminLinks(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerGroup('calendar', __('admin::calendar.navigation.group'), 20);
        $registry->registerGroup('config', __('admin::config.group'), 30);
        $registry->registerGroup('moderation', __('admin::moderation.navigation_group'), 40);
        $registry->registerGroup('story', __('admin::story.group'), 70);
        $registry->registerGroup('auth', __('admin::auth.user_management'), 80);
        $registry->registerGroup('faq', __('admin::faq.navigation.group'), 90);
        $registry->registerGroup('events', __('admin::domain_events.navigation_group'), 100);

        // Auth resources
        $registry->registerPage(
            'auth.users',
            'auth',
            __('admin::auth.users.navigation_label'),
            AdminRegistryTarget::route('auth.admin.users.index'),
            'groups',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        $registry->registerPage(
            'auth.roles',
            'auth',
            __('admin::auth.role.navigation_label'),
            AdminRegistryTarget::url('/admin/roles'),
            'admin_panel_settings',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            2,
        );

        $registry->registerPage(
            'auth.activation_codes',
            'auth',
            __('admin::auth.activation_codes.navigation_label'),
            AdminRegistryTarget::url('/admin/auth/activation-codes'),
            'key',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            3,
        );

        // Calendar
        $registry->registerPage(
            'calendar.activities',
            'calendar',
            __('admin::calendar.navigation.activities'),
            AdminRegistryTarget::url('/admin/calendar/activities'),
            'calendar_month',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        // Events — now registered in EventsServiceProvider (custom admin page)

        // FAQ
        $registry->registerPage(
            'faq.categories',
            'faq',
            __('admin::faq.navigation.categories'),
            AdminRegistryTarget::url('/admin/f-a-q/faq-categories'),
            'folder',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        $registry->registerPage(
            'faq.questions',
            'faq',
            __('admin::faq.navigation.questions'),
            AdminRegistryTarget::url('/admin/f-a-q/faq-questions'),
            'help',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            2,
        );

        // Moderation — reasons and reports now registered in ModerationServiceProvider (custom admin pages)

        // Story references
        // NOTE: All StoryRef admin pages are now registered in StoryServiceRefProvider (custom admin pages)
    }
}
