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

Route::middleware(['auth', 'verified'])->group(function () {
    // Profile viewing routes
    Route::get('/profile', [ProfileController::class, 'showOwn'])->name('profile.show.own');
    Route::get('/profile/edit', [ProfileManagementController::class, 'edit'])->name('profile.edit');
    Route::get('/profile/{user}', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileManagementController::class, 'update'])->name('profile.update');
    
    // Profile picture management
    Route::post('/profile/picture', [ProfileManagementController::class, 'uploadPicture'])->name('profile.picture.upload');
    Route::delete('/profile/picture', [ProfileManagementController::class, 'deletePicture'])->name('profile.picture.delete');
});
