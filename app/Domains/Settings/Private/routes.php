<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Settings\Private\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'role:' . Roles::USER . ',' . Roles::USER_CONFIRMED])->prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])->name('index');
    Route::get('/{tab}', [SettingsController::class, 'tab'])->name('tab');
    Route::put('/{tab}/{key}', [SettingsController::class, 'update'])->name('update');
    Route::delete('/{tab}/{key}', [SettingsController::class, 'reset'])->name('reset');
});
