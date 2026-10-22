<?php

use App\Domains\Fanart\Private\Controllers\DiscordFeedController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/discord/fanarts')
    ->middleware(['api', 'discord.api'])
    ->group(function () {
        // Same allowance as the notifications feed: one poll and one report per minute plus retries.
        Route::get('/pending', [DiscordFeedController::class, 'pending'])
            ->middleware('throttle:60,1')
            ->name('fanart.discord.pending');

        Route::post('/mark-sent', [DiscordFeedController::class, 'markSent'])
            ->middleware('throttle:60,1')
            ->name('fanart.discord.markSent');
    });
