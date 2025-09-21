<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Search\Private\Controllers\SearchController;

Route::middleware(['web'])
    ->group(function () {
        Route::get('/search/partial', [SearchController::class, 'partial'])->name('search.partial');
    });
