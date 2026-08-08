<?php

declare(strict_types=1);

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Calendar\Private\Activities\QuoteContest\Http\Controllers\QuoteContestCategoryController;
use App\Domains\Calendar\Private\Activities\QuoteContest\Http\Controllers\QuoteContestEntryController;
use App\Domains\Calendar\Private\Activities\QuoteContest\Http\Controllers\QuoteContestModerationController;
use App\Domains\Calendar\Private\Activities\QuoteContest\Http\Controllers\QuoteContestVoteController;
use Illuminate\Support\Facades\Route;

// Category CRUD: admin, tech-admin and moderator (same staff set as Calendar
// activity admin). Contest dates ride along with the activity form.
// No PATCH anywhere — the production WAF resets that verb.
Route::middleware(['web', 'auth', 'role:' . Roles::ADMIN . ',' . Roles::TECH_ADMIN . ',' . Roles::MODERATOR])
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

// Reader routes. The activity's own `role_restrictions` gate the page; these
// re-check phase and ownership themselves, since a forged POST never went past
// a rendered page.
Route::middleware(['web', 'auth', 'verified'])
    ->prefix('calendar/quote-contest/{activity}')
    ->name('quote-contest.')
    ->group(function () {
        Route::post('/entries', [QuoteContestEntryController::class, 'store'])
            ->name('entries.store');

        Route::delete('/entries/{entry}', [QuoteContestEntryController::class, 'destroy'])
            ->name('entries.destroy');

        // The ballot is idempotent: one PUT on the category the reader is
        // voting in, replacing whatever they had chosen there.
        Route::put('/votes/{category}', [QuoteContestVoteController::class, 'update'])
            ->name('votes.update');
    });

// Moderation. The role check is the controller's, not middleware's, so it reads
// the same constant as the *Résultats* tab it belongs to (§3.5).
Route::middleware(['web', 'auth'])
    ->prefix('calendar/quote-contest/{activity}')
    ->name('quote-contest.moderation.')
    ->group(function () {
        Route::delete('/moderation/entries/{entry}', [QuoteContestModerationController::class, 'destroy'])
            ->name('entries.destroy');
    });
