<?php

declare(strict_types=1);

use App\Domains\Calendar\Private\Activities\SecretGift\Http\Controllers\SecretGiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'verified'])->group(function () {
    Route::post('/calendar/secret-gift/{activity}/gift', [SecretGiftController::class, 'saveGift'])
        ->name('secret-gift.save-gift');

    Route::get('/calendar/secret-gift/{activity}/image/{assignment}', [SecretGiftController::class, 'serveImage'])
        ->name('secret-gift.image');
});
