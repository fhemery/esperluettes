<?php


// Include auth routes from Auth domain
require app_path('Domains/Auth/routes.php');

// Profile routes are loaded via its ServiceProvider

// Include news routes from News domain
// (moved) News routes are loaded via App\Domains\News\Public\Providers\NewsServiceProvider

// Home routes are loaded via App\Domains\Home\Public\Providers\HomeServiceProvider

// Dashboard routes are loaded via App\Domains\Dashboard\Public\Providers\DashboardServiceProvider

// Story routes are loaded via App\Domains\Story\Public\Providers\StoryServiceProvider

// StaticPage routes are loaded via App\Domains\StaticPage\Public\Providers\StaticPageServiceProvider
