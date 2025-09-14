<?php

namespace App\Domains\Comment\PublicApi\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Comment\PublicApi\CommentPolicyRegistry;
use App\Domains\Comment\PublicApi\CommentMaintenancePublicApi;
use App\Domains\Comment\Repositories\CommentRepository;
use App\Domains\Comment\View\Components\CommentList;
use Illuminate\Support\Facades\Blade;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Comment\Events\CommentPosted;

class CommentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Share a single policy registry instance across the app/tests
        $this->app->singleton(CommentPolicyRegistry::class, function () {
            return new CommentPolicyRegistry();
        });

        // Maintenance API (system-level operations)
        $this->app->singleton(CommentMaintenancePublicApi::class, function ($app) {
            return new CommentMaintenancePublicApi($app->make(CommentRepository::class));
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

        // Register Comment domain events
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(CommentPosted::name(), CommentPosted::class);
    }
}
