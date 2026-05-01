<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Follow\Private\Controllers\FollowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant', 'role:' . Roles::USER_CONFIRMED . ',' . Roles::USER])->group(function () {
    Route::post('/follow/{userId}', [FollowController::class, 'follow'])->name('follow.follow');
    Route::delete('/follow/{userId}', [FollowController::class, 'unfollow'])->name('follow.unfollow');
});
