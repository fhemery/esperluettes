<?php

use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;
use App\Domains\Message\Private\Controllers\MessageController;

Route::middleware(['web', 'auth', 'compliant'])
    ->prefix('messages')
    ->name('messages.')
    ->group(function () {
        Route::middleware(['role:'.Roles::ADMIN.','.Roles::TECH_ADMIN.','.Roles::MODERATOR])->group(function () {
            Route::get('/compose', [MessageController::class, 'compose'])->name('compose');
            Route::post('/', [MessageController::class, 'store'])->name('store');
        });
        Route::get('/', [MessageController::class, 'index'])->name('index');
        Route::get('/{delivery}', [MessageController::class, 'show'])->name('show');
        Route::delete('/{delivery}', [MessageController::class, 'destroy'])->name('destroy');
        
        
    });
