<?php

use App\Domains\Administration\Private\Controllers\DashboardController;
use App\Domains\Administration\Private\Controllers\LogsController;
use App\Domains\Administration\Private\Controllers\MaintenanceController;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('administration')->name('administration.')->group(function () {
    Route::get('', [DashboardController::class, 'index'])->name('dashboard')
        ->middleware(['auth', 'role:'.Roles::MODERATOR.','.Roles::ADMIN.','.Roles::TECH_ADMIN]);
        
    Route::middleware(['auth', 'role:'.Roles::TECH_ADMIN])->group(function () {
        Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance');
        Route::post('maintenance/empty-cache', [MaintenanceController::class, 'emptyCache'])->name('maintenance.empty-cache');
        Route::get('logs', [LogsController::class, 'index'])->name('logs');
        Route::get('logs/download/{file}', [LogsController::class, 'download'])->name('logs.download');
    });
});
