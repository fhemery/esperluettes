<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Statistics\Private\Controllers\Admin\StatisticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant'])->group(function () {
    Route::middleware(['role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
        ->prefix('admin/statistics')
        ->name('statistics.admin.')
        ->group(function () {
            Route::get('/', [StatisticsController::class, 'index'])->name('index');
        });
});
