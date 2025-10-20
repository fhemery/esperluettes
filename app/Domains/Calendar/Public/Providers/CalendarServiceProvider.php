<?php

declare(strict_types=1);

namespace App\Domains\Calendar\Public\Providers;

use App\Domains\Calendar\Private\Activities\Jardino\JardinoRegistration;
use Illuminate\Support\ServiceProvider;
use App\Domains\Calendar\Public\Api\CalendarRegistry;

class CalendarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CalendarRegistry::class, fn () => new CalendarRegistry());
    }

    public function boot(): void
    {
        // Migrations and any future routes/views
        $this->loadMigrationsFrom(app_path('Domains/Calendar/Database/Migrations'));

        // Register PHP translations under 'calendar' namespace
        $this->loadTranslationsFrom(app_path('Domains/Calendar/Private/Resources/lang'), 'calendar');

        $registry = app(CalendarRegistry::class);
        $registry->register(JardinoRegistration::ACTIVITY_TYPE, new JardinoRegistration());
    }
}
