<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\SecretGift\Http\Controllers\SecretGiftController;
use App\Domains\Calendar\Private\Activities\SecretGift\Http\Controllers\SecretGiftParticipantController;
use App\Domains\Calendar\Private\Activities\SecretGift\Http\Controllers\SecretGiftShuffleController;
use Illuminate\Support\Facades\Route;

// The shuffle, reached from the panel embedded in the activity edit page. Same
// role set as the base activities admin CRUD (functional spec §3 includes
// moderators), which is broader than QuoteContest's category routes.
Route::middleware(['web', 'auth', 'role:' . Roles::MODERATOR . ',' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
    ->prefix('admin/calendar/secret-gift/{activity}')
    ->name('calendar.admin.secret-gift.')
    ->group(function () {
        Route::post('/shuffle', [SecretGiftShuffleController::class, 'shuffle'])->name('shuffle');
    });

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::post('/calendar/secret-gift/{activity}/gift', [SecretGiftController::class, 'saveGift'])
        ->name('secret-gift.save-gift');

    // Enrolment. No PATCH: the production front-end resets that verb.
    Route::post('/calendar/secret-gift/{activity}/participants', [SecretGiftParticipantController::class, 'store'])
        ->name('secret-gift.participants.store');

    Route::put('/calendar/secret-gift/{activity}/participants', [SecretGiftParticipantController::class, 'update'])
        ->name('secret-gift.participants.update');

    Route::delete('/calendar/secret-gift/{activity}/participants', [SecretGiftParticipantController::class, 'destroy'])
        ->name('secret-gift.participants.destroy');

    Route::get('/calendar/secret-gift/{activity}/image/{assignment}', [SecretGiftController::class, 'serveImage'])
        ->name('secret-gift.image');

    Route::get('/calendar/secret-gift/{activity}/sound/{assignment}', [SecretGiftController::class, 'streamSound'])
        ->name('secret-gift.sound');

    Route::get('/calendar/secret-gift/{activity}/image/{assignment}/download', [SecretGiftController::class, 'serveImage'])
        ->name('secret-gift.download-image');

    Route::get('/calendar/secret-gift/{activity}/sound/{assignment}/download', [SecretGiftController::class, 'downloadSound'])
        ->name('secret-gift.download-sound');
});
