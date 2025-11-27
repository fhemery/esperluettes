<?php

use App\Domains\ReadList\Private\Controllers\ReadListController;
use Illuminate\Support\Facades\Route;
use App\Domains\Auth\Public\Api\Roles;

// ReadList routes - auth required
Route::middleware(['web', 'auth', 'compliant', 'role:' . Roles::USER_CONFIRMED . ',' . Roles::USER ])->group(function () {
    Route::get('/readlist', [ReadListController::class, 'index'])
        ->name('readlist.index');
    Route::post('/readlist/{storyId}', [ReadListController::class, 'add'])
        ->name('readlist.add');
    
    Route::delete('/readlist/{storyId}', [ReadListController::class, 'remove'])
        ->name('readlist.remove');
    
    Route::get('/readlist/load-more', [ReadListController::class, 'loadMore'])
        ->name('readlist.load-more');
    
    Route::get('/readlist/{storyId}/chapters', [ReadListController::class, 'chapters'])
        ->name('readlist.chapters');
});
