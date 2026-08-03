<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Providers;

use App\Domains\Administration\Public\Contracts\AdminNavigationRegistry;
use App\Domains\Administration\Public\Contracts\AdminRegistryTarget;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\Jardino\JardinoRegistration;
use App\Domains\Calendar\Private\Activities\QuoteContest\QuoteContestRegistration;
use App\Domains\Calendar\Private\Activities\SecretGift\SecretGiftRegistration;
use App\Domains\Calendar\Private\Support\ActivityMediaUsageProvider;
use App\Domains\Media\Public\Contracts\MediaUsageRegistry;
use Illuminate\Support\ServiceProvider;
use App\Domains\Calendar\Public\Api\CalendarRegistry;
use Illuminate\Support\Facades\Blade;

class CalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CalendarRegistry::class, fn () => new CalendarRegistry());
        // Register activity-specific providers (per-activity assets)
        $this->app->register(\App\Domains\Calendar\Private\Activities\Jardino\JardinoServiceProvider::class);
        $this->app->register(\App\Domains\Calendar\Private\Activities\SecretGift\SecretGiftServiceProvider::class);
        $this->app->register(\App\Domains\Calendar\Private\Activities\QuoteContest\QuoteContestServiceProvider::class);
    }

    public function boot(): void
    {
        // Migrations and any future routes/views
        $this->loadMigrationsFrom(app_path('Domains/Calendar/Database/Migrations'));

        // Load routes
        $this->loadRoutesFrom(app_path('Domains/Calendar/Private/routes.php'));

        // Register views under the 'calendar' namespace
        $this->loadViewsFrom(app_path('Domains/Calendar/Private/Resources/views'), 'calendar');

        // Register PHP components
        Blade::componentNamespace('App\\Domains\\Calendar\\Private\\View\\Components', 'calendar');

        // Register anonymous components
        Blade::anonymousComponentPath(app_path('Domains/Calendar/Private/Resources/views/components'), 'calendar');

        // Register PHP translations under 'calendar' namespace
        $this->loadTranslationsFrom(app_path('Domains/Calendar/Private/Resources/lang'), 'calendar');

        // Let Media GC know which image files Calendar still uses (activity images).
        app(MediaUsageRegistry::class)->register(new ActivityMediaUsageProvider());

        $registry = app(CalendarRegistry::class);
        $registry->register(JardinoRegistration::ACTIVITY_TYPE, new JardinoRegistration());
        $registry->register(SecretGiftRegistration::ACTIVITY_TYPE, new SecretGiftRegistration());
        $registry->register(QuoteContestRegistration::ACTIVITY_TYPE, new QuoteContestRegistration());

        $this->registerAdminNavigation();
    }

    protected function registerAdminNavigation(): void
    {
        $registry = app(AdminNavigationRegistry::class);

        $registry->registerPage(
            'calendar.activities',
            'calendar',
            'calendar::admin.activities.nav_label',
            AdminRegistryTarget::route('calendar.admin.activities.index'),
            'calendar_month',
            [Roles::ADMIN, Roles::TECH_ADMIN, Roles::MODERATOR],
            1,
        );
    }
}
