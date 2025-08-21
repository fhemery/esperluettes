<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Story\Http\Controllers\StoryCreateController;
use App\Domains\Story\Http\Controllers\StoryController;

Route::middleware(['web'])->group(function () {
    Route::get('/stories', [StoryController::class, 'index'])
        ->name('stories.index');

    Route::middleware(['role:user-confirmed'])->group(function () {
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
