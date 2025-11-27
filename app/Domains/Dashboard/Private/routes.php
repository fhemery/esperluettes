<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Dashboard\Private\Controllers\DashboardController;

Route::middleware('web')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware(['auth', 'verified', 'compliant', 'role:'.Roles::USER.','.Roles::USER_CONFIRMED.','.Roles::ADMIN])
        ->name('dashboard');
});
