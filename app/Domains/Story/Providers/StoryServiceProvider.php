<?php

namespace App\Domains\Story\Providers;

use App\Domains\Comment\PublicApi\CommentPolicyRegistry;
use App\Domains\Story\Models\Story;
use App\Domains\Story\Models\Chapter;
use App\Domains\Story\Policies\StoryPolicy;
use App\Domains\Story\Policies\ChapterPolicy;
use App\Domains\Story\Services\ChapterCommentPolicy;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Story\Events\StoryCreated;
use App\Domains\Story\Events\StoryUpdated;
use App\Domains\Story\Events\StoryDeleted;
use App\Domains\Story\Events\ChapterCreated;
use App\Domains\Story\Events\ChapterUpdated;
use App\Domains\Story\Events\ChapterPublished;
use App\Domains\Story\Events\ChapterUnpublished;
use App\Domains\Story\Events\ChapterDeleted;
use App\Domains\Story\Events\StoryVisibilityChanged;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class StoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Bindings for Story domain can be added here later
    }

    public function boot(): void
    {
        // Load domain migrations
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

        // Register views under the 'story' namespace
        $this->loadViewsFrom(app_path('Domains/Story/Views'), 'story');

        // Load PHP translations for the Story domain under 'story::'
        $this->loadTranslationsFrom(__DIR__ . '/../Resources/lang', 'story');

        // Register policies
        Gate::policy(Story::class, StoryPolicy::class);
        Gate::policy(Chapter::class, ChapterPolicy::class);

        // Register policies
        $registry = app(CommentPolicyRegistry::class);
        $policy = app(ChapterCommentPolicy::class);
        $registry->register('chapter', $policy);

        // Register Story domain events mapping
        app(EventBus::class)->registerEvent(StoryCreated::name(), StoryCreated::class);
        app(EventBus::class)->registerEvent(StoryUpdated::name(), StoryUpdated::class);
        app(EventBus::class)->registerEvent(StoryDeleted::name(), StoryDeleted::class);
        app(EventBus::class)->registerEvent(ChapterCreated::name(), ChapterCreated::class);
        app(EventBus::class)->registerEvent(ChapterUpdated::name(), ChapterUpdated::class);
        app(EventBus::class)->registerEvent(ChapterPublished::name(), ChapterPublished::class);
        app(EventBus::class)->registerEvent(ChapterUnpublished::name(), ChapterUnpublished::class);
        app(EventBus::class)->registerEvent(ChapterDeleted::name(), ChapterDeleted::class);
        app(EventBus::class)->registerEvent(StoryVisibilityChanged::name(), StoryVisibilityChanged::class);
    }
}
