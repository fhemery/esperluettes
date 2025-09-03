<?php

namespace App\Domains\Comment\PublicApi\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Comment\PublicApi\CommentPolicyRegistry;
use App\Domains\Comment\View\Components\CommentList;
use Illuminate\Support\Facades\Blade;

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

        // Register Blade components
        Blade::component('comment-list', CommentList::class);

        // Load domain routes
        $this->loadRoutesFrom(app_path('Domains/Comment/Routes/web.php'));
    }
}
