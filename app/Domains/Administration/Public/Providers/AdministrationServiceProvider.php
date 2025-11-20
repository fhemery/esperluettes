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
        $registry->registerGroup('news', __('admin::news.navigation.group'), 50);
        $registry->registerGroup('static', __('admin::static.navigation.group'), 60);
        $registry->registerGroup('story', __('admin::story.group'), 70);
        $registry->registerGroup('auth', __('admin::auth.user_management'), 80);
        $registry->registerGroup('faq', __('admin::faq.navigation.group'), 90);
        $registry->registerGroup('events', __('admin::domain_events.navigation_group'), 100);

        // Auth resources
        $registry->registerPage(
            'auth.users',
            'auth',
            __('admin::auth.users.navigation_label'),
            AdminRegistryTarget::url('/admin/auth/users'),
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

        // Config
        $registry->registerPage(
            'config.feature_toggles',
            'config',
            __('admin::config.feature_toggles.nav_label'),
            AdminRegistryTarget::url('/admin/config/feature-toggles'),
            'tune',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        // Events
        $registry->registerPage(
            'events.domain_events',
            'events',
            __('admin::domain_events.navigation_label'),
            AdminRegistryTarget::url('/admin/event/domain-events'),
            'bolt',
            [Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR],
            1,
        );

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

        // Moderation
        $registry->registerPage(
            'moderation.reasons',
            'moderation',
            __('admin::moderation.reason.navigation_label'),
            AdminRegistryTarget::url('/admin/moderation/reasons'),
            'flag',
            [Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR],
            1,
        );

        $registry->registerPage(
            'moderation.reports',
            'moderation',
            __('admin::moderation.reports.navigation_label'),
            AdminRegistryTarget::url('/admin/moderation/moderation-reports'),
            'report',
            [Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR],
            2,
        );

        // News
        $registry->registerPage(
            'news.news',
            'news',
            __('admin::news.navigation.news'),
            AdminRegistryTarget::url('/admin/news/news'),
            'campaign',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        $registry->registerPage(
            'news.pinned',
            'news',
            __('admin::news.navigation.pinned_order'),
            AdminRegistryTarget::url('/admin/news/pinned-news'),
            'push_pin',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            2,
        );

        // Static pages
        $registry->registerPage(
            'static.pages',
            'static',
            __('admin::static.navigation.pages'),
            AdminRegistryTarget::url('/admin/static-page/static-pages'),
            'description',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        // Story references
        $registry->registerPage(
            'story.audiences',
            'story',
            __('admin::story.audience.navigation_label'),
            AdminRegistryTarget::url('/admin/story/audiences'),
            'group',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            1,
        );

        $registry->registerPage(
            'story.copyrights',
            'story',
            __('admin::story.copyright.navigation_label'),
            AdminRegistryTarget::url('/admin/story/copyrights'),
            'copyright',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            2,
        );

        $registry->registerPage(
            'story.feedbacks',
            'story',
            __('admin::story.feedback.navigation_label'),
            AdminRegistryTarget::url('/admin/story/feedbacks'),
            'forum',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            3,
        );

        $registry->registerPage(
            'story.genres',
            'story',
            __('admin::story.genre.navigation_label'),
            AdminRegistryTarget::url('/admin/story/genres'),
            'menu_book',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            4,
        );

        $registry->registerPage(
            'story.statuses',
            'story',
            __('admin::story.status.navigation_label'),
            AdminRegistryTarget::url('/admin/story/statuses'),
            'flag',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            5,
        );

        $registry->registerPage(
            'story.trigger_warnings',
            'story',
            __('admin::story.trigger_warning.navigation_label'),
            AdminRegistryTarget::url('/admin/story/trigger-warnings'),
            'warning',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            6,
        );

        $registry->registerPage(
            'story.types',
            'story',
            __('admin::story.type.navigation_label'),
            AdminRegistryTarget::url('/admin/story/types'),
            'category',
            [Roles::ADMIN, Roles::TECH_ADMIN],
            7,
        );
    }
}
