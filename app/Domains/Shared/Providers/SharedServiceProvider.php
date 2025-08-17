<?php

namespace App\Domains\Shared\Providers;

use App\Domains\Shared\Views\Layouts\AppLayout;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Domains\Shared\Listeners\AuditAllDomainEvents;

class SharedServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register any bindings or services
    }

    public function boot(): void
    {
        // Note: CSS and JS assets are located in app/Domains/Shared/Resources/
        // and are managed by Vite configuration (see vite.config.js)
        
        // Register domain-specific migrations (infrastructure/framework tables)
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        
        // Register language files
        $this->loadJsonTranslationsFrom(
            __DIR__.'/../Resources/lang',
            'fr'
        );

        // Register the view namespaces
        $this->loadViewsFrom(
            app_path('Domains/Shared/Resources/views'),
            'shared'
        );

        // Register anonymous components, both unprefixed (<x-editor>) and prefixed (<x-shared::editor>)
        Blade::anonymousComponentPath(app_path('Domains/Shared/Resources/views/components'));
        Blade::anonymousComponentPath(app_path('Domains/Shared/Resources/views/components'), 'shared');
        
        // Register vendor notification views
        $this->loadViewsFrom(
            app_path('Domains/Shared/Views'),
            'vendor/notifications'
        );

        // Register the layout components
        Blade::component('app-layout', AppLayout::class);
        
        // Also register with the shared namespace for explicit usage
        Blade::component('shared::app-layout', AppLayout::class);

        // Register wildcard event auditing if enabled
        if (config('shared.event_auditing_enabled', true)) {
            Event::listen('*', [AuditAllDomainEvents::class, 'handle']);
        }
    }
}
