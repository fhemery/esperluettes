<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Story\Private\Controllers\StoryCreateController;
use App\Domains\Story\Private\Controllers\StoryController;
use App\Domains\Story\Private\Controllers\ChapterController;
use App\Domains\Story\Private\Controllers\ReadingProgressController;

Route::middleware(['web'])->group(function () {
    Route::get('/stories', [StoryController::class, 'index'])
        ->name('stories.index');

    Route::middleware(['role:'.Roles::USER_CONFIRMED])->group(function () {
        Route::get('/stories/create', [StoryCreateController::class, 'create'])
            ->name('stories.create');

        Route::post('/stories', [StoryController::class, 'store'])
            ->name('stories.store');

        // Chapters: reorder (authors/co-authors only; controller enforces 404 on unauthorized)
        // MUST be defined before the generic /chapters/{chapterSlug} routes to avoid greedy matching
        Route::put('/stories/{storySlug}/chapters/reorder', [ChapterController::class, 'reorder'])
            ->where(['storySlug' => '.*'])
            ->name('chapters.reorder');

        // Chapters: edit + update (authors/co-authors only; controllers enforce 404 on unauthorized)
        // To put before story edit to avoid agressive matching
        Route::get('/stories/{storySlug}/chapters/{chapterSlug}/edit', [ChapterController::class, 'edit'])
            ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
            ->name('chapters.edit');
        // Reading progress & stats endpoints (CSRF-protected)
        // Logged users: toggle read/unread
        Route::middleware(['auth'])->group(function () {
            Route::post('/stories/{storySlug}/chapters/{chapterSlug}/read', [ReadingProgressController::class, 'markRead'])
                ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
                ->name('chapters.read.mark');

            Route::delete('/stories/{storySlug}/chapters/{chapterSlug}/read', [ReadingProgressController::class, 'unmarkRead'])
                ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
                ->name('chapters.read.unmark');
        });

        Route::match(['put', 'patch'], '/stories/{storySlug}/chapters/{chapterSlug}', [ChapterController::class, 'update'])
            ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
            ->name('chapters.update');

        // Edit/update own stories (authors only)
        Route::get('/stories/{slug}/edit', [StoryController::class, 'edit'])
            ->where('slug', '.*')
            ->name('stories.edit');

        Route::match(['put', 'patch'], '/stories/{slug}', [StoryController::class, 'update'])
            ->where('slug', '.*')
            ->name('stories.update');

        // Chapters: delete (authors/co-authors only; controller enforces 404 on unauthorized)
        // IMPORTANT: place BEFORE the story delete route to avoid greedy match and accidental story deletions
        Route::delete('/stories/{storySlug}/chapters/{chapterSlug}', [ChapterController::class, 'destroy'])
            ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
            ->name('chapters.destroy');

        // Hard delete own story (authors only)
        Route::delete('/stories/{slug}', [StoryController::class, 'destroy'])
            ->where('slug', '.*')
            ->name('stories.destroy');

        // Chapters: create + store (authors/co-authors only; controllers enforce 404 on unauthorized)
        Route::get('/stories/{storySlug}/chapters/create', [ChapterController::class, 'create'])
            ->where('storySlug', '.*')
            ->name('chapters.create');

        Route::post('/stories/{storySlug}/chapters', [ChapterController::class, 'store'])
            ->where('storySlug', '.*')
            ->name('chapters.store');

        // Reading progress & stats endpoints (CSRF-protected)
        // Logged users: toggle read/unread
        Route::middleware(['auth'])->group(function () {
            Route::post('/stories/{storySlug}/chapters/{chapterSlug}/read', [ReadingProgressController::class, 'markRead'])
                ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
                ->name('chapters.read.mark');

            Route::delete('/stories/{storySlug}/chapters/{chapterSlug}/read', [ReadingProgressController::class, 'unmarkRead'])
                ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
                ->name('chapters.read.unmark');
        });
    });

    // Chapter public show route (US-039 path with /chapters segment)
    // IMPORTANT: define before the generic /stories/{slug} route to avoid greedy matching.
    Route::get('/stories/{storySlug}/chapters/{chapterSlug}', [ChapterController::class, 'show'])
        ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
        ->name('chapters.show');

    // Public show route (visibility enforcement handled in controller/policies later)
    Route::get('/stories/{slug}', [StoryController::class, 'show'])
        ->where('slug', '.*')
        ->name('stories.show');

    // Profile-owned stories partial (expects ?user_id=, optional &showPrivate=true)
    Route::get('/profiles/{slug}/stories', [StoryController::class, 'profileStories'])
        ->where('slug', '.*')
        ->name('stories.for-profile');
});
