<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Calendar\Private\Activities\Jardino\Http\Controllers\JardinoDashboardController;
use App\Domains\Calendar\Private\Activities\Jardino\Http\Controllers\JardinoFlowerController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('calendar/activities/{activity}')
    ->group(function () {
        Route::post('/jardino/goal', [JardinoDashboardController::class, 'createGoal'])
            ->name('jardino.goal.create');

        Route::post('/jardino/plant-flower', [JardinoFlowerController::class, 'plantFlower'])
            ->name('jardino.flower.plant');

        Route::post('/jardino/remove-flower', [JardinoFlowerController::class, 'removeFlower'])
            ->name('jardino.flower.remove');

        Route::post('/jardino/block-cell', [JardinoFlowerController::class, 'blockCell'])
            ->name('jardino.cell.block');

        Route::post('/jardino/unblock-cell', [JardinoFlowerController::class, 'unblockCell'])
            ->name('jardino.cell.unblock');
    });
