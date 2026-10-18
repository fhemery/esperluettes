<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Fanart\Private\Controllers\FanartController;
use App\Domains\Fanart\Private\Controllers\FanartFileController;
use App\Domains\Fanart\Private\Controllers\FanartFragmentController;
use App\Domains\Fanart\Private\Controllers\FanartistController;
use App\Domains\Fanart\Private\Controllers\FanartProfileFragmentController;
use App\Domains\Fanart\Private\Controllers\FanartModerationController;
use App\Domains\Fanart\Private\Controllers\FanartReviewController;
use App\Domains\Fanart\Private\Controllers\RoleRequestController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant', 'role:' . implode(',', [
    Roles::USER_CONFIRMED,
    Roles::MODERATOR,
    Roles::ADMIN,
    Roles::TECH_ADMIN,
])])->group(function () {
    // Lazy HTML fragments: 403 through FanartPolicy (seeFeature / view), never a redirect for a reader of a hidden story.
    Route::get('/stories/{slug}/fanarts', [FanartFragmentController::class, 'tab'])
        ->where('slug', '[^/]+')->name('fanart.tab');
    Route::get('/stories/{slug}/fanarts/groups/{group}', [FanartFragmentController::class, 'group'])
        ->where('slug', '[^/]+')->where('group', 'work|\d+')->name('fanart.group');
    // Profile tab « Fanarts »: 403 through FanartPolicy::seeProfileTab; `?kind=image|audio`.
    Route::get('/fanarts/profile/{userId}/groups/{storyId}', [FanartProfileFragmentController::class, 'group'])
        ->whereNumber('userId')->whereNumber('storyId')->name('fanart.profile.group');
    Route::get('/fanarts/{fanart}', [FanartFragmentController::class, 'show'])
        ->whereNumber('fanart')->name('fanart.show');
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
    // Authors only through ManageFanartistsRequest (403); the `role` middleware would redirect instead.
    Route::post('/stories/{slug}/fanartistes', [FanartistController::class, 'store'])
        ->where('slug', '[^/]+')->name('fanart.fanartists.store');
    Route::delete('/stories/{slug}/fanartistes/{userId}', [FanartistController::class, 'destroy'])
        ->where('slug', '[^/]+')->whereNumber('userId')->name('fanart.fanartists.destroy');
    // Authorised by RoleRequestRequest (403): `requestRole`, the requester, or an author.
    Route::post('/stories/{slug}/fanartiste-requests', [RoleRequestController::class, 'store'])
        ->where('slug', '[^/]+')->name('fanart.role-requests.store');
    Route::delete('/stories/{slug}/fanartiste-requests/{request}', [RoleRequestController::class, 'destroy'])
        ->where('slug', '[^/]+')->whereNumber('request')->name('fanart.role-requests.destroy');
    Route::post('/stories/{slug}/fanartiste-requests/{request}/accept', [RoleRequestController::class, 'accept'])
        ->where('slug', '[^/]+')->whereNumber('request')->name('fanart.role-requests.accept');
    Route::post('/stories/{slug}/fanartiste-requests/{request}/decline', [RoleRequestController::class, 'decline'])
        ->where('slug', '[^/]+')->whereNumber('request')->name('fanart.role-requests.decline');
    Route::get('/fanarts/{fanart}/file', [FanartFileController::class, 'original'])
        ->whereNumber('fanart')->name('fanart.file');
    Route::get('/fanarts/{fanart}/thumb/{width}', [FanartFileController::class, 'thumb'])
        ->whereNumber('fanart')->whereNumber('width')->name('fanart.thumb');
    // Staff-only through FanartPolicy::moderate (403); the `role` middleware would redirect instead.
    Route::delete('/moderation/fanarts/{fanart}', [FanartModerationController::class, 'destroy'])
        ->whereNumber('fanart')->name('fanart.moderation.destroy');
});
