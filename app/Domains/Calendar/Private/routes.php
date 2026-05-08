<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Calendar\Private\Controllers\Admin\ActivityController;
use App\Domains\Calendar\Private\Controllers\CalendarController;

Route::middleware(['web', 'auth', 'compliant', 'role:'.Roles::USER.','.Roles::USER_CONFIRMED.','.Roles::ADMIN])
    ->group(function () {
        Route::get('/activities/{slug}', [CalendarController::class, 'show'])
            ->name('calendar.activities.show');
    });

Route::middleware(['web', 'auth', 'role:'.Roles::ADMIN.','.Roles::TECH_ADMIN])
    ->prefix('admin/calendar')
    ->name('calendar.admin.')
    ->group(function () {
        Route::resource('activities', ActivityController::class)->except(['show']);
    });
