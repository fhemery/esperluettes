<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Quote\Private\Controllers\QuoteController;
use App\Domains\Quote\Private\Controllers\QuoteProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant', 'role:' . Roles::USER_CONFIRMED])
    ->prefix('quotes')
    ->name('quotes.')
    ->group(function () {
        Route::get('/', [QuoteController::class, 'index'])->name('index');
        Route::post('/', [QuoteController::class, 'store'])->name('store');
        Route::put('/{quoteId}', [QuoteController::class, 'updateNote'])->name('update-note');
        Route::delete('/{quoteId}', [QuoteController::class, 'destroy'])->name('destroy');
    });

Route::middleware(['web'])
    ->prefix('quotes/profile')
    ->group(function () {
        Route::get('/{profileSlug}', [QuoteProfileController::class, 'show'])
            ->name('quotes.profile.show');
    });
