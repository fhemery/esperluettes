<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Story\Private\Controllers\StoryCreateController;
use App\Domains\Story\Private\Controllers\StoryController;
use App\Domains\Story\Private\Controllers\ChapterController;
use App\Domains\Story\Private\Controllers\ChapterModerationController;
use App\Domains\Story\Private\Controllers\ReadingProgressController;
use App\Domains\Story\Private\Controllers\StoryModerationController;
use App\Domains\Story\Private\Controllers\CollaboratorController;
use App\Domains\Story\Private\Controllers\ProfileCommentsApiController;

Route::middleware(['web'])->group(function () {
    Route::get('/stories', [StoryController::class, 'index'])
        ->name('stories.index');

    // Profile comments API - fetch comments for a story by user (for async loading)
    // MUST be defined before routes with greedy {slug} patterns
    Route::middleware(['auth', 'compliant', 'role:' . Roles::USER_CONFIRMED])->group(function () {
        Route::get('/stories/{storyId}/profile-comments/{userId}', [ProfileCommentsApiController::class, 'getCommentsForStory'])
            ->where(['storyId' => '[0-9]+', 'userId' => '[0-9]+'])
            ->name('profile.comments.api');
    });

    Route::middleware(['role:' . Roles::MODERATOR . ',' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])->group(function () {
        Route::post('/stories/{slug}/moderation/make-private', [StoryModerationController::class, 'makePrivate'])
            ->where('slug', '.*')
            ->name('stories.moderation.make-private');

        Route::post('/stories/{slug}/moderation/empty-summary', [StoryModerationController::class, 'emptySummary'])
            ->where('slug', '.*')
            ->name('stories.moderation.empty-summary');

        Route::post('/stories/{slug}/moderation/remove-cover', [StoryModerationController::class, 'removeCover'])
            ->where('slug', '.*')
            ->name('stories.moderation.remove-cover');

        Route::post('/chapters/{slug}/moderation/unpublish', [ChapterModerationController::class, 'unpublish'])
            ->where('slug', '.*')
            ->name('chapters.moderation.unpublish');

        Route::post('/chapters/{slug}/moderation/empty-content', [ChapterModerationController::class, 'emptyContent'])
            ->where('slug', '.*')
            ->name('chapters.moderation.empty-content');
    });

    // Reading progress & stats endpoints (CSRF-protected)
    // Available to all authenticated users (both USER and USER_CONFIRMED roles)
    Route::middleware(['auth', 'compliant'])->group(function () {
        Route::post('/stories/{storySlug}/chapters/{chapterSlug}/read', [ReadingProgressController::class, 'markRead'])
            ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
            ->name('chapters.read.mark');

        Route::delete('/stories/{storySlug}/chapters/{chapterSlug}/read', [ReadingProgressController::class, 'unmarkRead'])
            ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
            ->name('chapters.read.unmark');
    });

    Route::middleware(['role:' . Roles::USER_CONFIRMED])->group(function () {
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

        Route::match(['put', 'patch'], '/stories/{storySlug}/chapters/{chapterSlug}', [ChapterController::class, 'update'])
            ->where(['storySlug' => '.*', 'chapterSlug' => '.*'])
            ->name('chapters.update');

        // Collaborator management (authors only; controller enforces 404 on unauthorized)
        Route::get('/stories/{slug}/collaborators', [CollaboratorController::class, 'index'])
            ->where('slug', '.*')
            ->name('stories.collaborators.index');

        Route::post('/stories/{slug}/collaborators', [CollaboratorController::class, 'store'])
            ->where('slug', '.*')
            ->name('stories.collaborators.store');

        Route::delete('/stories/{slug}/collaborators/{targetUserId}', [CollaboratorController::class, 'destroy'])
            ->where('slug', '.*')
            ->name('stories.collaborators.destroy');

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


    // Collaborator leave route - accessible by both USER and USER_CONFIRMED (beta-readers can be USER only)
    Route::middleware(['auth', 'compliant'])->group(function () {
        Route::post('/stories/{slug}/collaborators/leave', [CollaboratorController::class, 'leave'])
            ->where('slug', '.*')
            ->name('stories.collaborators.leave');
    });

});
