<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Story\Http\Controllers\StoryCreateController;
use App\Domains\Story\Http\Controllers\StoryController;

Route::middleware(['web'])->group(function () {
    Route::middleware(['auth', 'verified'])->group(function () {
        Route::get('/stories/create', [StoryCreateController::class, 'create'])
            ->name('stories.create');

        Route::post('/stories', [StoryController::class, 'store'])
            ->name('stories.store');
    });

    // Public show route (visibility enforcement handled in controller/policies later)
    Route::get('/stories/{slug}', [StoryController::class, 'show'])
        ->where('slug', '.*')
        ->name('stories.show');
});
