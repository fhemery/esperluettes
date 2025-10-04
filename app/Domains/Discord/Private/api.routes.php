<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Discord\Private\Controllers\Api\AuthController;

Route::prefix('api/discord')
    ->middleware(['api', 'discord.api'])
    ->group(function () {
        Route::post('/users', [AuthController::class, 'connect'])
            ->middleware('throttle:100,1')
            ->name('discord.api.users.store');
    });
