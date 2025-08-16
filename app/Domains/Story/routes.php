<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Story\Http\Controllers\StoryCreateController;

Route::middleware(['web'])->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::get('/stories/create', [StoryCreateController::class, 'create'])
            ->name('stories.create');
    });
});
