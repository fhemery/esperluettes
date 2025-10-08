<?php

namespace App\Domains\Comment\Public\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Comment\Public\Api\CommentMaintenancePublicApi;
use App\Domains\Comment\Private\Repositories\CommentRepository;
use App\Domains\Comment\Private\View\Components\CommentList;
use Illuminate\Support\Facades\Blade;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Comment\Public\Events\CommentEdited;
use App\Domains\Auth\Public\Events\UserDeleted;
use App\Domains\Comment\Private\Listeners\RemoveAuthorOnUserDeleted;

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
        $this->loadMigrationsFrom(app_path('Domains/Comment/Database/Migrations'));
        $this->loadViewsFrom(app_path('Domains/Comment/Private/Resources/views'), 'comment');
        $this->loadTranslationsFrom(app_path('Domains/Comment/Private/Resources/lang'), 'comment');

        // Register Blade components (both namespaced and alias)
        // Global alias: <x-comment-list /> (used across Story and tests)
        Blade::component(CommentList::class, 'comment-list');
        // Optional namespaced variant: <x-comment::comment-list /> if we enable the component namespace later
        // Blade::componentNamespace('App\\Domains\\Comment\\Private\\View\\Components', 'comment');
        Blade::anonymousComponentPath(app_path('Domains/Comment/Private/Resources/views/components'), 'comment');

        // Load domain routes
        $this->loadRoutesFrom(app_path('Domains/Comment/Private/routes.php'));

        // Register Comment domain events
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(CommentPosted::name(), CommentPosted::class);
        $eventBus->registerEvent(CommentEdited::name(), CommentEdited::class);
        // Subscribe to Auth.UserDeleted to nullify comment authors
        $eventBus->subscribe(UserDeleted::name(), [RemoveAuthorOnUserDeleted::class, 'handle']);
    }
}
