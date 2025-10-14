<?php

namespace App\Domains\Moderation\Providers;

use App\Domains\Moderation\Public\Services\ModerationRegistry;
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
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
    }
}
