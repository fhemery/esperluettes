<?php

use Illuminate\Support\Facades\Route;
use App\Domains\StaticPage\Private\Controllers\StaticPageController;

// Catch-all route for static pages. Must be included after all specific routes.
Route::middleware('web')->group(function () {
    Route::get('/{slug}', [StaticPageController::class, 'show'])
        ->where('slug', '^(?!stories|admin|dashboard|profile|news|comment|search|api|auth|login|register|verification|verify-email|email|password|filament).+')
        ->name('static.show');
});
