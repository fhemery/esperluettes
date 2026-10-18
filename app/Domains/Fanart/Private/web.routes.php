<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Controllers\FanartController;
use App\Domains\Fanart\Private\Controllers\FanartFileController;
use App\Domains\Fanart\Private\Controllers\FanartModerationController;
use App\Domains\Fanart\Private\Controllers\FanartReviewController;
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
    Route::get('/fanarts/{fanart}/edit', [FanartController::class, 'edit'])
        ->whereNumber('fanart')->name('fanart.edit');
    Route::put('/fanarts/{fanart}', [FanartController::class, 'update'])
        ->whereNumber('fanart')->name('fanart.update');
    Route::delete('/fanarts/{fanart}', [FanartController::class, 'destroy'])
        ->whereNumber('fanart')->name('fanart.destroy');
    // `fanartId` is not a model binding: a declined fanart is gone and must answer "already decided", not 404.
    Route::post('/fanarts/{fanartId}/accept', [FanartReviewController::class, 'accept'])
        ->whereNumber('fanartId')->name('fanart.accept');
    Route::post('/fanarts/{fanartId}/decline', [FanartReviewController::class, 'decline'])
        ->whereNumber('fanartId')->name('fanart.decline');
    Route::get('/fanarts/{fanart}/file', [FanartFileController::class, 'original'])
        ->whereNumber('fanart')->name('fanart.file');
    Route::get('/fanarts/{fanart}/thumb/{width}', [FanartFileController::class, 'thumb'])
        ->whereNumber('fanart')->whereNumber('width')->name('fanart.thumb');
    // Staff-only through FanartPolicy::moderate (403); the `role` middleware would redirect instead.
    Route::delete('/moderation/fanarts/{fanart}', [FanartModerationController::class, 'destroy'])
        ->whereNumber('fanart')->name('fanart.moderation.destroy');
});
