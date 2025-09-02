<?php

namespace App\Domains\Comment\PublicApi\Providers;

use Illuminate\Support\ServiceProvider;

class CommentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings required: services are auto-wired to concrete classes
    }

    public function boot(): void
    {
        // Load domain assets; safe if directories don't exist yet
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->loadViewsFrom(app_path('Domains/Comment/Views'), 'comment');
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'comment');
    }
}
