<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Domains\Calendar\Private\Activities\Jardino\Http\Controllers\JardinoDashboardController;

Route::middleware(['web', 'auth', 'verified'])
    ->prefix('calendar/activities/{activity}')
    ->group(function () {
        Route::post('/jardino/goal', [JardinoDashboardController::class, 'createGoal'])
            ->name('jardino.goal.create');
    });
