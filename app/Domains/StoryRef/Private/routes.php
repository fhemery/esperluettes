<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Private\Controllers\Admin\AudienceController;
use App\Domains\StoryRef\Private\Controllers\Admin\CopyrightController;
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
        });
});
