<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Comment\Http\Controllers\CommentController;

Route::middleware(['web', 'auth'])
    ->group(function () {
        Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
        Route::patch('/comments/{commentId}', [CommentController::class, 'update'])->name('comments.update');
    });
