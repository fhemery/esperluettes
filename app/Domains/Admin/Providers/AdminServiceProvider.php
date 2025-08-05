<?php

namespace App\Domains\Admin\Providers;

use Illuminate\Support\ServiceProvider;

class AdminServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register language files
        $this->loadJsonTranslationsFrom(
            __DIR__.'/../resources/lang',
            'fr'
        );

        // Register admin views namespace
        \Illuminate\Support\Facades\View::addNamespace('admin', app_path('Domains/Admin/Views'));
    }
}
