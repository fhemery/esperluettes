<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Comment\Http\Controllers\CommentController;

Route::middleware(['web', 'auth'])
    ->prefix('comments')
    ->name('comments.')
    ->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('store');
    });
