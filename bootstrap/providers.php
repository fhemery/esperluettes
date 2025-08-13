<?php

return [
    // Shared services
    App\Domains\Shared\Providers\SharedServiceProvider::class,
    
    // Domain-specific providers
    App\Domains\Admin\Providers\AdminServiceProvider::class,
    App\Domains\Announcement\Providers\AnnouncementServiceProvider::class,
    App\Domains\Auth\Providers\AuthServiceProvider::class,
    App\Domains\Profile\Providers\ProfileServiceProvider::class,
    App\Domains\Story\Providers\StoryServiceProvider::class,
    
    // Add other domain providers here as they are created
];
