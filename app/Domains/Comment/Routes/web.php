<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Comment\Http\Controllers\CommentController;
use App\Domains\Comment\Http\Controllers\CommentFragmentController;

Route::middleware(['web', 'auth'])
    ->prefix('comments')
    ->name('comments.')
    ->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('store');
    });

// Public HTML fragment endpoint for lazy-loading the comments list
Route::middleware(['web'])
    ->prefix('comments')
    ->name('comments.')
    ->group(function () {
        Route::get('/fragments', [CommentFragmentController::class, 'items'])->name('fragments');
    });
