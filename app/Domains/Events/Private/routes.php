<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Events\Private\Controllers\Admin\DomainEventController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN . ',' . Roles::MODERATOR])
    ->prefix('admin/events')
    ->name('events.admin.')
    ->group(function () {
        Route::post('domain-events/bulk-destroy', [DomainEventController::class, 'bulkDestroy'])->name('domain-events.bulk-destroy');
        Route::resource('domain-events', DomainEventController::class)->only(['index', 'show', 'destroy']);
    });
