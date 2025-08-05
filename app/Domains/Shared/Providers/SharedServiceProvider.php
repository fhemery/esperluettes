<?php

namespace App\Domains\Shared\Providers;

use App\Domains\Shared\View\Components\AppLayout;
use App\Domains\Shared\View\Components\GuestLayout;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class SharedServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Register any bindings or services
    }

    public function boot()
    {
        // Register the view namespaces
        $this->loadViewsFrom(
            app_path('Domains/Shared/Resources/views'),
            'shared'
        );

        Blade::anonymousComponentPath(app_path('Domains/Shared/Resources/views/components'));
        
        // Register vendor notification views
        $this->loadViewsFrom(
            app_path('Domains/Shared/Views'),
            'vendor/notifications'
        );

        // Register the layout components
        Blade::component('app-layout', AppLayout::class);
        Blade::component('guest-layout', GuestLayout::class);
        
        // Also register them with the shared namespace for explicit usage
        Blade::component('shared::app-layout', AppLayout::class);
        Blade::component('shared::guest-layout', GuestLayout::class);
    }
}
