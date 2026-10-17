<?php

use Illuminate\Support\Facades\Route;

Route::prefix('api/fanart')
    ->middleware(['api', 'discord.api'])
    ->group(function () {
        // Discord bot endpoints land here in phase 22.
    });
