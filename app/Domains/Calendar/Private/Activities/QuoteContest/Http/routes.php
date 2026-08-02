<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\QuoteContest\Http\Controllers\QuoteContestCategoryController;
use Illuminate\Support\Facades\Route;

// Category CRUD only: the contest's dates ride along with the activity form.
// No PATCH anywhere — the production WAF resets that verb.
Route::middleware(['web', 'auth', 'role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN])
    ->prefix('admin/calendar/quote-contest/{activity}')
    ->name('calendar.admin.quote-contest.')
    ->group(function () {
        Route::post('/categories', [QuoteContestCategoryController::class, 'store'])
            ->name('categories.store');

        Route::put('/categories/{category}', [QuoteContestCategoryController::class, 'update'])
            ->name('categories.update');

        Route::delete('/categories/{category}', [QuoteContestCategoryController::class, 'destroy'])
            ->name('categories.destroy');
    });
