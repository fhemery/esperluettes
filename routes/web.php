<?php


// Include auth routes from Auth domain
require app_path('Domains/Auth/routes.php');

// Profile routes are loaded via its ServiceProvider

// Include news routes from News domain
// (moved) News routes are loaded via App\Domains\News\Public\Providers\NewsServiceProvider

// Include home routes from Home domain
require app_path('Domains/Home/routes.php');

// Include dashboard routes from Dashboard domain
require app_path('Domains/Dashboard/routes.php');

// Include story routes from Story domain
require app_path('Domains/Story/routes.php');

// Include comment routes from Comment domain
require app_path('Domains/Comment/routes.php');

// StaticPage routes are loaded via App\Domains\StaticPage\Public\Providers\StaticPageServiceProvider
