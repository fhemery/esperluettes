<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Notification\Private\Controllers\NotificationController;

Route::middleware(['web', 'auth', 'role:'.Roles::USER.','.Roles::USER_CONFIRMED])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
});
