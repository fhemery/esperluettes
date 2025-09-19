<?php

return [
    // Shared services
    App\Domains\Shared\Providers\SharedServiceProvider::class,
    // Events domain
    App\Domains\Events\Providers\EventsServiceProvider::class,
    
    // Domain-specific providers
    App\Domains\Admin\Providers\AdminServiceProvider::class,
    App\Domains\News\Providers\NewsServiceProvider::class,
    App\Domains\Home\Providers\HomeServiceProvider::class,
    App\Domains\Dashboard\Providers\DashboardServiceProvider::class,
    App\Domains\Auth\Providers\AuthServiceProvider::class,
    App\Domains\Profile\Public\Providers\ProfileServiceProvider::class,
    App\Domains\StoryRef\Providers\StoryServiceRefProvider::class,
    App\Domains\Comment\PublicApi\Providers\CommentServiceProvider::class,
    App\Domains\Story\Providers\StoryServiceProvider::class,
    App\Domains\StaticPage\Providers\StaticPageServiceProvider::class,
    App\Domains\Search\Public\Providers\SearchServiceProvider::class,
    
    // Add other domain providers here as they are created
];
