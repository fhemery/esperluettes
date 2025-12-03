<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Private\Controllers\Admin\AudienceController;
use App\Domains\StoryRef\Private\Controllers\Admin\CopyrightController;
use App\Domains\StoryRef\Private\Controllers\Admin\FeedbackController;
use App\Domains\StoryRef\Private\Controllers\Admin\GenreController;
use App\Domains\StoryRef\Private\Controllers\Admin\StatusController;
use App\Domains\StoryRef\Private\Controllers\Admin\TriggerWarningController;
use App\Domains\StoryRef\Private\Controllers\Admin\TypeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant'])->group(function () {
    // Admin routes for StoryRef
    Route::middleware(['role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
        ->prefix('admin/story-ref')
        ->name('story_ref.admin.')
        ->group(function () {
            // Audiences - custom routes must come before resource to avoid conflict with {audience} parameter
            Route::put('audiences/reorder', [AudienceController::class, 'reorder'])->name('audiences.reorder');
            Route::get('audiences/export', [AudienceController::class, 'export'])->name('audiences.export');
            Route::resource('audiences', AudienceController::class)->except(['show']);

            // Copyrights
            Route::put('copyrights/reorder', [CopyrightController::class, 'reorder'])->name('copyrights.reorder');
            Route::get('copyrights/export', [CopyrightController::class, 'export'])->name('copyrights.export');
            Route::resource('copyrights', CopyrightController::class)->except(['show']);

            // Feedbacks
            Route::put('feedbacks/reorder', [FeedbackController::class, 'reorder'])->name('feedbacks.reorder');
            Route::get('feedbacks/export', [FeedbackController::class, 'export'])->name('feedbacks.export');
            Route::resource('feedbacks', FeedbackController::class)->except(['show']);

            // Genres
            Route::put('genres/reorder', [GenreController::class, 'reorder'])->name('genres.reorder');
            Route::get('genres/export', [GenreController::class, 'export'])->name('genres.export');
            Route::resource('genres', GenreController::class)->except(['show']);

            // Statuses
            Route::put('statuses/reorder', [StatusController::class, 'reorder'])->name('statuses.reorder');
            Route::get('statuses/export', [StatusController::class, 'export'])->name('statuses.export');
            Route::resource('statuses', StatusController::class)->except(['show']);

            // Trigger Warnings
            Route::put('trigger-warnings/reorder', [TriggerWarningController::class, 'reorder'])->name('trigger-warnings.reorder');
            Route::get('trigger-warnings/export', [TriggerWarningController::class, 'export'])->name('trigger-warnings.export');
            Route::resource('trigger-warnings', TriggerWarningController::class)->except(['show']);

            // Types
            Route::put('types/reorder', [TypeController::class, 'reorder'])->name('types.reorder');
            Route::get('types/export', [TypeController::class, 'export'])->name('types.export');
            Route::resource('types', TypeController::class)->except(['show']);
        });
});
