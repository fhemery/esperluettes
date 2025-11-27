<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Profile\Private\Controllers\ProfileController;
use App\Domains\Profile\Private\Controllers\ProfileLookupController;
use App\Domains\Profile\Private\Controllers\ProfileModerationController;
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

Route::middleware('web')->prefix('profile')->group(function () {
    // Protected routes for own profile and editing
    Route::middleware(['role:' . Roles::USER . ',' . Roles::USER_CONFIRMED])->group(function () {
        Route::get('/', [ProfileController::class, 'showOwn'])->name('profile.show.own');
        Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/', [ProfileController::class, 'update'])->name('profile.update');
    });

    Route::middleware(['role:' . Roles::MODERATOR . ',' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
        ->prefix('/{profile:slug}/moderation')->group(function () {
            Route::post('/remove-image', [ProfileModerationController::class, 'removeImage'])->name('profile.moderation.remove-image');
            Route::post('/empty-about', [ProfileModerationController::class, 'emptyAbout'])->name('profile.moderation.empty-about');
            Route::post('/empty-social', [ProfileModerationController::class, 'emptySocial'])->name('profile.moderation.empty-social');
    });
   
    // Auth protected small lookup endpoints for UI components
    Route::middleware(['auth', 'compliant'])->group(function () {
        Route::get('/lookup', [ProfileLookupController::class, 'search'])
            ->middleware(['throttle:60,1'])
            ->name('profiles.lookup');
        Route::get('/lookup/by-ids', [ProfileLookupController::class, 'byIds'])
            ->middleware(['throttle:60,1'])
            ->name('profiles.lookup.by_ids');
    });

     // Public profile page (by slug) - accessible to guests
    Route::get('/{profile:slug}', [ProfileController::class, 'show'])->name('profile.show');

});
