<?php

use Illuminate\Support\Facades\Route;


// Include auth routes from Auth domain
require app_path('Domains/Auth/routes.php');

// Include profile routes from Profile domain
require app_path('Domains/Profile/routes.php');

// Include news routes from News domain
require app_path('Domains/News/routes.php');

// Include home routes from Home domain
require app_path('Domains/Home/routes.php');

// Include dashboard routes from Dashboard domain
require app_path('Domains/Dashboard/routes.php');

// Include story routes from Story domain
require app_path('Domains/Story/routes.php');

// Include static page routes from StaticPage domain (must be last due to catch-all)
require app_path('Domains/StaticPage/routes.php');
