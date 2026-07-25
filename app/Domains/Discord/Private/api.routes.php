<?php

use App\Domains\Discord\Private\Controllers\Api\NotificationsController;
use App\Domains\Discord\Private\Controllers\Api\UsersController;
use Illuminate\Support\Facades\Route;

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

        // The bot polls once a minute and reports delivery once per poll; the
        // allowance leaves room for retries and backoff without permitting hammering.
        Route::get('/notifications/pending', [NotificationsController::class, 'pending'])
            ->middleware('throttle:60,1')
            ->name('discord.api.notifications.pending');

        Route::post('/notifications/mark-sent', [NotificationsController::class, 'markSent'])
            ->middleware('throttle:60,1')
            ->name('discord.api.notifications.markSent');
    });
