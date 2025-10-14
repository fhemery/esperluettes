<?php

namespace App\Domains\Config\Public\Providers;

use Illuminate\Support\ServiceProvider;

class ConfigServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load migrations for the Config domain
        $this->loadMigrationsFrom(app_path('Domains/Config/Database/Migrations'));
    }
}
