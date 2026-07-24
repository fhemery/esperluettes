<?php

use App\Domains\Media\Private\Controllers\MediaLibraryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/media/library', [MediaLibraryController::class, 'index'])
        ->name('media.library');
});
