<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');


// Include auth routes from Auth domain
require app_path('Domains/Auth/routes.php');

// Include profile routes from Profile domain
require app_path('Domains/Profile/routes.php');

// Include announcement routes from Announcement domain
require app_path('Domains/Announcement/routes.php');
