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

        // Edit/update own stories (authors only)
        Route::get('/stories/{slug}/edit', [StoryController::class, 'edit'])
            ->where('slug', '.*')
            ->name('stories.edit');

        Route::match(['put', 'patch'], '/stories/{slug}', [StoryController::class, 'update'])
            ->where('slug', '.*')
            ->name('stories.update');

        // Hard delete own story (authors only)
        Route::delete('/stories/{slug}', [StoryController::class, 'destroy'])
            ->where('slug', '.*')
            ->name('stories.destroy');
    });

    // Public show route (visibility enforcement handled in controller/policies later)
    Route::get('/stories/{slug}', [StoryController::class, 'show'])
        ->where('slug', '.*')
        ->name('stories.show');

    // Profile-owned stories partial (expects ?user_id=, optional &showPrivate=true)
    Route::get('/profiles/{slug}/stories', [StoryController::class, 'profileStories'])
        ->where('slug', '.*')
        ->name('stories.for-profile');
});
