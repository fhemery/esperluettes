<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Calendar\Private\Controllers\CalendarController;

Route::middleware(['web', 'auth', 'role:'.Roles::USER.','.Roles::USER_CONFIRMED.','.Roles::ADMIN])
    ->group(function () {
        Route::get('/activities/{slug}', [CalendarController::class, 'show'])
            ->name('calendar.activities.show');
    });
