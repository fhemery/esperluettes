<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Controllers\FanartController;
use App\Domains\Fanart\Private\Controllers\FanartFileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant', 'role:' . implode(',', [
    Roles::USER_CONFIRMED,
    Roles::MODERATOR,
    Roles::ADMIN,
    Roles::TECH_ADMIN,
])])->group(function () {
    Route::get('/stories/{slug}/fanarts/create', [FanartController::class, 'create'])
        ->where('slug', '[^/]+')->name('fanart.create');
    Route::post('/stories/{slug}/fanarts', [FanartController::class, 'store'])
        ->where('slug', '[^/]+')->name('fanart.store');
    Route::get('/fanarts/{fanart}/file', [FanartFileController::class, 'original'])
        ->whereNumber('fanart')->name('fanart.file');
    Route::get('/fanarts/{fanart}/thumb/{width}', [FanartFileController::class, 'thumb'])
        ->whereNumber('fanart')->whereNumber('width')->name('fanart.thumb');
});
