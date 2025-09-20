<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Comment\Private\Controllers\CommentController;

Route::middleware(['web', 'auth'])
    ->prefix('comments')
    ->name('comments.')
    ->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('store');
        Route::patch('/{commentId}', [CommentController::class, 'update'])->name('update');
    });

// Public HTML fragment endpoint for lazy-loading the comments list
Route::middleware(['web'])
    ->prefix('comments')
    ->name('comments.')
    ->group(function () {
        Route::get('/fragments', [CommentController::class, 'items'])->name('fragments');
    });
