<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StoryRef\Private\Controllers\Admin\AudienceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant'])->group(function () {
    // Admin routes for StoryRef
    Route::middleware(['role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
        ->prefix('admin/story-ref')
        ->name('story_ref.admin.')
        ->group(function () {
            // Audiences CRUD
            Route::resource('audiences', AudienceController::class)->except(['show']);
        });
});
