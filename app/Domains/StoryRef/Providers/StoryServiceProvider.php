<?php

namespace App\Domains\StoryRef\Providers;

use Illuminate\Support\ServiceProvider;

class StoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register Story domain services here if needed.
    }

    public function boot(): void
    {
        // Load migrations from the Story domain
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        // Optionally: publish or load translations/views later as the domain grows.
    }
}
