<?php

use App\Domains\News\Private\Controllers\NewsController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::get('/news', [NewsController::class, 'index'])->name('news.index');
    Route::get('/news/{slug}', [NewsController::class, 'show'])->name('news.show');
});
