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

// Protected routes for own profile and editing
Route::middleware(['role:user,user-confirmed'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'showOwn'])->name('profile.show.own');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

// Public profile page (by slug) - accessible to guests
Route::get('/profile/{profile:slug}', [ProfileController::class, 'show'])->name('profile.show');
