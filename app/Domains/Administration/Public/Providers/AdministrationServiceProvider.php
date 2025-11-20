<?php

declare(strict_types=1);

namespace App\Domains\Administration\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
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
    }

    protected function registerAdminPages(): void
    {
        $registry = app(AdminNavigationRegistry::class);
        $techDomain = __('administration::admin.category.label');
        $registry->registerGroup($techDomain, $techDomain, 10);

        $registry->registerPage(
            __('administration::maintenance.key'),
            $techDomain,
            __('administration::maintenance.title'),
            '/administration/maintenance',
            'settings',
            [Roles::TECH_ADMIN],
            1,
        );

        $registry->registerPage(
            'logs',
            $techDomain,
            __('administration::logs.title'),
            '/administration/logs',
            'description',
            [Roles::TECH_ADMIN],
            2,
        );   
    }
}
