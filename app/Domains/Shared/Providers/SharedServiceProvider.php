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
        // Register the view namespace
        $this->loadViewsFrom(
            app_path('Domains/Shared/Resources/views'),
            'shared'
        );

        // Register the components with their original names
        Blade::component('app-layout', AppLayout::class);
        Blade::component('guest-layout', GuestLayout::class);
        
        // Also register them with the shared namespace for explicit usage
        Blade::component('shared::app-layout', AppLayout::class);
        Blade::component('shared::guest-layout', GuestLayout::class);
    }
}
