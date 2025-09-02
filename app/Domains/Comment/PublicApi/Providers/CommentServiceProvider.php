<?php

namespace App\Domains\Comment\PublicApi\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Comment\Services\CommentPolicyRegistry;

class CommentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Share a single policy registry instance across the app/tests
        $this->app->singleton(CommentPolicyRegistry::class, function () {
            return new CommentPolicyRegistry();
        });
    }

    public function boot(): void
    {
        // Load domain assets; safe if directories don't exist yet
        $this->loadMigrationsFrom(__DIR__ . '/../../Database/Migrations');
        $this->loadViewsFrom(app_path('Domains/Comment/Views'), 'comment');
        $this->loadTranslationsFrom(__DIR__ . '/../../Resources/lang', 'comment');
    }
}
