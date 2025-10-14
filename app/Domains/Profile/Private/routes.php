<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Private\Controllers\ProfileController;
use App\Domains\Profile\Private\Controllers\ProfileLookupController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Profile Domain Routes
|--------------------------------------------------------------------------
|
| Here is where you can register profile-related routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

Route::middleware('web')->group(function () {
    // Protected routes for own profile and editing
    Route::middleware(['role:' . Roles::USER . ',' . Roles::USER_CONFIRMED])->group(function () {
        Route::get('/profile', [ProfileController::class, 'showOwn'])->name('profile.show.own');
        Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    });

    // Public profile page (by slug) - accessible to guests
    Route::get('/profile/{profile:slug}', [ProfileController::class, 'show'])->name('profile.show');

    // Admin-only (auth) small lookup endpoints for UI components
    Route::middleware(['auth'])->group(function () {
        Route::get('/profiles/lookup', [ProfileLookupController::class, 'search'])
            ->middleware(['throttle:60,1'])
            ->name('profiles.lookup');
        Route::get('/profiles/lookup/by-ids', [ProfileLookupController::class, 'byIds'])
            ->middleware(['throttle:60,1'])
            ->name('profiles.lookup.by_ids');
    });
});
