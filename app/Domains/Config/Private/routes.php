<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Config\Private\Controllers\Admin\ConfigParameterController;
use App\Domains\Config\Private\Controllers\Admin\FeatureToggleController;
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

        Route::get('/feature-toggles', [FeatureToggleController::class, 'index'])
            ->name('config.admin.feature-toggles.index');
        Route::post('/feature-toggles/{featureToggle}/set-access', [FeatureToggleController::class, 'setAccess'])
            ->name('config.admin.feature-toggles.setAccess');

        Route::get('/feature-toggles/create', [FeatureToggleController::class, 'create'])
            ->name('config.admin.feature-toggles.create');
        Route::post('/feature-toggles', [FeatureToggleController::class, 'store'])
            ->name('config.admin.feature-toggles.store');
        Route::get('/feature-toggles/{featureToggle}/edit', [FeatureToggleController::class, 'edit'])
            ->name('config.admin.feature-toggles.edit');
        Route::put('/feature-toggles/{featureToggle}', [FeatureToggleController::class, 'update'])
            ->name('config.admin.feature-toggles.update');
        Route::delete('/feature-toggles/{featureToggle}', [FeatureToggleController::class, 'destroy'])
            ->name('config.admin.feature-toggles.destroy');
    });
