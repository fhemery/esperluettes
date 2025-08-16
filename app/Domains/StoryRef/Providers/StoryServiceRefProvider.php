<?php

namespace App\Domains\StoryRef\Providers;

use Illuminate\Support\ServiceProvider;

class StoryServiceRefProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register StoryRef domain services here if needed.
    }

    public function boot(): void
    {
        // Load migrations from the StoryRef domain
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        // Optionally: publish or load translations/views later as the domain grows.
    }
}
