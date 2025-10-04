<?php

namespace App\Domains\Story\Public\Providers;

use App\Domains\Comment\Public\Api\CommentPolicyRegistry;
use App\Domains\Story\Private\Models\Story;
use App\Domains\Story\Private\Models\Chapter;
use App\Domains\Story\Private\Observers\ChapterObserver;
use App\Domains\Story\Private\Policies\StoryPolicy;
use App\Domains\Story\Private\Policies\ChapterPolicy;
use App\Domains\Story\Private\Services\ChapterCommentPolicy;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Story\Public\Events\StoryCreated;
use App\Domains\Story\Public\Events\StoryUpdated;
use App\Domains\Story\Public\Events\StoryDeleted;
use App\Domains\Story\Public\Events\ChapterCreated;
use App\Domains\Story\Public\Events\ChapterUpdated;
use App\Domains\Story\Public\Events\ChapterPublished;
use App\Domains\Story\Public\Events\ChapterUnpublished;
use App\Domains\Story\Public\Events\ChapterDeleted;
use App\Domains\Story\Public\Events\StoryVisibilityChanged;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Comment\Public\Events\CommentPosted;
use App\Domains\Story\Private\Listeners\GrantInitialCreditsOnUserRegistered;
use App\Domains\Story\Private\Listeners\GrantCreditOnRootCommentPosted;

class StoryServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Load domain migrations
        $this->loadMigrationsFrom(app_path('Domains/Story/Database/Migrations'));

        // Load routes
        $this->loadRoutesFrom(app_path('Domains/Story/Private/routes.php'));

        // Register views under the 'story' namespace from Private resources
        $this->loadViewsFrom(app_path('Domains/Story/Private/Resources/views'), 'story');

        // Register PHP components
        Blade::componentNamespace('App\\Domains\\Story\\Private\\View\\Components', 'story');
        
        
        // Register anonymous components, both unprefixed and prefixed (<x-story::...>)
        Blade::anonymousComponentPath(app_path('Domains/Story/Private/Resources/views/components'), 'story');

        // Load PHP translations for the Story domain under 'story::'
        $this->loadTranslationsFrom(app_path('Domains/Story/Private/Resources/lang'), 'story');

        // Register policies
        Gate::policy(Story::class, StoryPolicy::class);
        Gate::policy(Chapter::class, ChapterPolicy::class);

        // Register model observers
        Chapter::observe(ChapterObserver::class);

        // Register policies
        $registry = app(CommentPolicyRegistry::class);
        $policy = app(ChapterCommentPolicy::class);
        $registry->register('chapter', $policy);

        // Register Story domain events mapping
        $eventBus = app(EventBus::class);
        $eventBus->registerEvent(StoryCreated::name(), StoryCreated::class);
        $eventBus->registerEvent(StoryUpdated::name(), StoryUpdated::class);
        $eventBus->registerEvent(StoryDeleted::name(), StoryDeleted::class);
        $eventBus->registerEvent(ChapterCreated::name(), ChapterCreated::class);
        $eventBus->registerEvent(ChapterUpdated::name(), ChapterUpdated::class);
        $eventBus->registerEvent(ChapterPublished::name(), ChapterPublished::class);
        $eventBus->registerEvent(ChapterUnpublished::name(), ChapterUnpublished::class);
        $eventBus->registerEvent(ChapterDeleted::name(), ChapterDeleted::class);
        $eventBus->registerEvent(StoryVisibilityChanged::name(), StoryVisibilityChanged::class);

        // Subscribe to cross-domain events (after-commit listeners)
        $eventBus->subscribe(UserRegistered::class, [app(GrantInitialCreditsOnUserRegistered::class), 'handle']);
        $eventBus->subscribe(CommentPosted::class, [app(GrantCreditOnRootCommentPosted::class), 'handle']);
    }
}
