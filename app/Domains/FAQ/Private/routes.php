<?php

use Illuminate\Support\Facades\Route;
use App\Domains\FAQ\Private\Controllers\FaqController;

Route::middleware(['web'])
    ->group(function () {
        Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');
        Route::get('/faq/{categorySlug}', [FaqController::class, 'index'])->name('faq.category');
    });
