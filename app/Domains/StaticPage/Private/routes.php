<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Auth\Public\Api\Roles;
use App\Domains\StaticPage\Private\Controllers\StaticPageController;
use App\Domains\StaticPage\Private\Controllers\Admin\StaticPageController as AdminStaticPageController;

// Admin routes
Route::middleware(['web', 'auth', 'compliant', 'role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
    ->prefix('admin/static-pages')
    ->name('static.admin.')
    ->group(function () {
        Route::patch('{staticPage}/publish', [AdminStaticPageController::class, 'publish'])->name('publish');
        Route::patch('{staticPage}/unpublish', [AdminStaticPageController::class, 'unpublish'])->name('unpublish');
        Route::resource('/', AdminStaticPageController::class)
            ->parameters(['' => 'staticPage'])
            ->names([
                'index' => 'index',
                'create' => 'create',
                'store' => 'store',
                'edit' => 'edit',
                'update' => 'update',
                'destroy' => 'destroy',
            ])
            ->except(['show']);
    });

// Catch-all route for static pages. Must be included after all specific routes.
Route::middleware('web')->group(function () {
    Route::get('/{slug}', [StaticPageController::class, 'show'])
        ->where('slug', '^(?!stories|admin|dashboard|profile|news|comment|search|api|auth|login|register|verification|verify-email|email|password|filament).+')
        ->name('static.show');
});
