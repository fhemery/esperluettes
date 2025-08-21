<?php

use App\Domains\Profile\Controllers\ProfileController;
use App\Domains\Profile\Controllers\ProfileManagementController;
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

Route::middleware(['role:user,user-confirmed'])->group(function () {
    // Profile viewing routes
    Route::get('/profile', [ProfileController::class, 'showOwn'])->name('profile.show.own');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/{profile:slug}', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
