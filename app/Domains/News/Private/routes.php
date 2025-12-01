<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\News\Private\Controllers\Admin\NewsController as AdminNewsController;
use App\Domains\News\Private\Controllers\Admin\PinnedNewsController;
use App\Domains\News\Private\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');
});

// Admin routes for News
Route::middleware(['web', 'auth', 'compliant', 'role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
    ->prefix('admin/news')
    ->name('news.admin.')
    ->group(function () {
        // Publish/Unpublish actions (before resource to avoid conflict)
        Route::patch('{news}/publish', [AdminNewsController::class, 'publish'])->name('publish');
        Route::patch('{news}/unpublish', [AdminNewsController::class, 'unpublish'])->name('unpublish');
        
        // Main news CRUD
        Route::resource('/', AdminNewsController::class)
            ->parameters(['' => 'news'])
            ->names([
                'index' => 'index',
                'create' => 'create',
                'store' => 'store',
                'edit' => 'edit',
                'update' => 'update',
                'destroy' => 'destroy',
            ])
            ->except(['show']);
        
        // Pinned news carousel management
        Route::get('pinned', [PinnedNewsController::class, 'index'])->name('pinned.index');
        Route::put('pinned/reorder', [PinnedNewsController::class, 'reorder'])->name('pinned.reorder');
    });
