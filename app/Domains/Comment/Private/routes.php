<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Comment\Private\Controllers\AnnotationController;
use App\Domains\Comment\Private\Controllers\AnnotationModerationController;
use App\Domains\Comment\Private\Controllers\AnnotationReplyController;
use App\Domains\Comment\Private\Controllers\CommentController;
use App\Domains\Comment\Private\Controllers\CommentModerationController;

Route::middleware(['web', 'auth', 'compliant'])
    ->prefix('comments')
    ->name('comments.')
    ->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('store');
        Route::patch('/{commentId}', [CommentController::class, 'update'])->name('update');
        Route::get('/{commentId}/annotations', [AnnotationController::class, 'index'])
            ->whereNumber('commentId')
            ->name('annotations.index');
        Route::put('/{commentId}/annotations', [AnnotationController::class, 'save'])
            ->whereNumber('commentId')
            ->name('annotations.save');
        Route::put('/annotations/{annotationId}/processed', [AnnotationController::class, 'processed'])
            ->whereNumber('annotationId')
            ->name('annotations.processed');
        Route::post('/annotations/{annotationId}/replies', [AnnotationReplyController::class, 'store'])
            ->whereNumber('annotationId')
            ->name('annotations.replies.store');
        Route::delete('/annotations/replies/{replyId}', [AnnotationReplyController::class, 'destroy'])
            ->whereNumber('replyId')
            ->name('annotations.replies.destroy');

        Route::middleware('role:'.Roles::MODERATOR.','.Roles::ADMIN.','.Roles::TECH_ADMIN)->name('moderation.')->group(function(){
            Route::delete('/annotations/{annotationId}', [AnnotationModerationController::class, 'delete'])
                ->whereNumber('annotationId')
                ->name('annotations.delete');
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
