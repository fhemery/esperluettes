<?php

use App\Domains\Administration\Private\Controllers\LogsController;
use App\Domains\Administration\Private\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('administration')->name('administration.')->group(function () {
    Route::middleware(['auth', 'role:tech-admin'])->group(function () {
        Route::get('maintenance', [MaintenanceController::class, 'index'])->name('maintenance');
        Route::post('maintenance/empty-cache', [MaintenanceController::class, 'emptyCache'])->name('maintenance.empty-cache');
        
        Route::get('logs', [LogsController::class, 'index'])->name('logs');
        Route::get('logs/download/{file}', [LogsController::class, 'download'])->name('logs.download');
    });
});
