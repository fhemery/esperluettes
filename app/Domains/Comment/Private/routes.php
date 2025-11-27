<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Comment\Private\Controllers\CommentController;
use App\Domains\Comment\Private\Controllers\CommentModerationController;

Route::middleware(['web', 'auth', 'compliant'])
    ->prefix('comments')
    ->name('comments.')
    ->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('store');
        Route::patch('/{commentId}', [CommentController::class, 'update'])->name('update');
        
        Route::middleware('role:'.Roles::MODERATOR.','.Roles::ADMIN.','.Roles::TECH_ADMIN)->name('moderation.')->group(function(){
            Route::post('/{commentId}/empty-content', [CommentModerationController::class, 'emptyContent'])->name('empty-content');
            Route::delete('/{commentId}', [CommentModerationController::class, 'delete'])->name('delete');
        });
    });

// Public HTML fragment endpoint for lazy-loading the comments list
Route::middleware(['web'])
    ->prefix('comments')
    ->name('comments.')
    ->group(function () {
        Route::get('/fragments', [CommentController::class, 'items'])->name('fragments');
    });
