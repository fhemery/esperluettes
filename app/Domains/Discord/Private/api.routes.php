<?php

use Illuminate\Support\Facades\Route;
use App\Domains\Discord\Private\Controllers\Api\UsersController;

Route::prefix('api/discord')
    ->middleware(['api', 'discord.api'])
    ->group(function () {
        Route::post('/users', [UsersController::class, 'connect'])
            ->middleware('throttle:100,1')
            ->name('discord.api.users.store');

        Route::get('/users/{discordId}', [UsersController::class, 'show'])
            ->middleware('throttle:300,1')
            ->name('discord.api.users.show');

        Route::delete('/users/{discordId}', [UsersController::class, 'destroy'])
            ->middleware('throttle:100,1')
            ->name('discord.api.users.destroy');
    });
