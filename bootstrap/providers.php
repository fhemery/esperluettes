<?php

return [
    // Shared services
    App\Domains\Shared\Providers\SharedServiceProvider::class,
    // Events domain
    App\Domains\Config\Public\Providers\ConfigServiceProvider::class,
    App\Domains\Events\Public\Providers\EventsServiceProvider::class,
    App\Domains\Moderation\Public\Providers\ModerationServiceProvider::class,
    
    // Domain-specific providers
    App\Domains\Admin\Providers\AdminServiceProvider::class,
    App\Domains\News\Public\Providers\NewsServiceProvider::class,
    App\Domains\Home\Public\Providers\HomeServiceProvider::class,
    App\Domains\Dashboard\Public\Providers\DashboardServiceProvider::class,
    App\Domains\Auth\Public\Providers\AuthServiceProvider::class,
    App\Domains\Profile\Public\Providers\ProfileServiceProvider::class,
    App\Domains\StoryRef\Public\Providers\StoryServiceRefProvider::class,
    App\Domains\Comment\Public\Providers\CommentServiceProvider::class,
    App\Domains\Message\Public\Providers\MessageServiceProvider::class,
    App\Domains\Story\Public\Providers\StoryServiceProvider::class,
    App\Domains\Search\Public\Providers\SearchServiceProvider::class,
    App\Domains\Discord\Public\Providers\DiscordServiceProvider::class,
    App\Domains\FAQ\Private\Providers\FaqServiceProvider::class,
    App\Domains\Calendar\Public\Providers\CalendarServiceProvider::class,
    
    // Add other domain providers here as they are created

    // This one should be put last, because it declares catch-all routes
    App\Domains\StaticPage\Public\Providers\StaticPageServiceProvider::class,
];

