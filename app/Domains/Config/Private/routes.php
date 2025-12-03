<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Private\Controllers\Admin\ConfigParameterController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/config')
    ->middleware(['web', 'auth', 'role:'.Roles::TECH_ADMIN.','.Roles::ADMIN])
    ->group(function () {
        Route::get('/parameters', [ConfigParameterController::class, 'index'])
            ->name('config.admin.parameters.index');
        Route::put('/parameters/{domain}/{key}', [ConfigParameterController::class, 'update'])
            ->name('config.admin.parameters.update');
        Route::delete('/parameters/{domain}/{key}', [ConfigParameterController::class, 'reset'])
            ->name('config.admin.parameters.reset');
    });
