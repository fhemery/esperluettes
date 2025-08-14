<?php

use Illuminate\Support\Facades\Route;
use App\Domains\StaticPage\Controllers\StaticPageController;

// Catch-all route for static pages. Must be included after all specific routes.
Route::get('/{slug}', [StaticPageController::class, 'show'])->name('static.show');
