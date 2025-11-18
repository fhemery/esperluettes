<?php

namespace App\Domains\Moderation\Public\Providers;

use App\Domains\Moderation\Public\Services\ModerationRegistry;
use App\Domains\Moderation\Public\Events\ReportSubmitted;
use App\Domains\Moderation\Public\Events\ReportApproved;
use App\Domains\Moderation\Public\Events\ReportRejected;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ModerationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register ModerationRegistry as a singleton
        $this->app->singleton(ModerationRegistry::class);
    }

    public function boot(): void
    {
        // Register domain-specific migrations
        $this->loadMigrationsFrom(app_path('Domains/Moderation/Database/Migrations'));

        // Register routes
        $this->loadRoutesFrom(app_path('Domains/Moderation/Private/routes.php'));

        // Register translations
        $this->loadTranslationsFrom(app_path('Domains/Moderation/Private/Resources/lang'), 'moderation');

        // Register views
        $this->loadViewsFrom(app_path('Domains/Moderation/Private/Resources/views'), 'moderation');

        // Register PHP and anonymous components
        // Private components (e.g. moderation icon)
        Blade::componentNamespace('App\\Domains\\Moderation\\Private\\View\\Components', 'moderation');
        // Public components are registered individually because of namespace collision (admin user management)
        Blade::component(
            \App\Domains\Moderation\Public\View\Components\AdminUserManagementComponent::class,
            'moderation::admin-user-management-component'
        );
        Blade::anonymousComponentPath(app_path('Domains/Moderation/Private/Resources/views/components'), 'moderation');

        // Register Moderation domain events
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(ReportSubmitted::name(), ReportSubmitted::class);
        $eventBus->registerEvent(ReportApproved::name(), ReportApproved::class);
        $eventBus->registerEvent(ReportRejected::name(), ReportRejected::class);
    }
}
