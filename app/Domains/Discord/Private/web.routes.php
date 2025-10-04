<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Discord\Private\Controllers\Web\CodeController;

Route::middleware(['web', 'auth'])
    ->group(function () {
        Route::post('/discord/connect/code', [CodeController::class, 'generate'])
            ->name('discord.web.connect.code');
    });
