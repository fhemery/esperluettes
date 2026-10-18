<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Controllers\FanartFileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant', 'role:' . implode(',', [
    Roles::USER_CONFIRMED,
    Roles::MODERATOR,
    Roles::ADMIN,
    Roles::TECH_ADMIN,
])])->group(function () {
    Route::get('/fanarts/{fanart}/file', [FanartFileController::class, 'original'])
        ->whereNumber('fanart')->name('fanart.file');
    Route::get('/fanarts/{fanart}/thumb/{width}', [FanartFileController::class, 'thumb'])
        ->whereNumber('fanart')->whereNumber('width')->name('fanart.thumb');
});
