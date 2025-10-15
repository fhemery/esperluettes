<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Moderation\Private\Controllers\ModerationReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    // Moderation routes - require authentication and verified email
    Route::middleware(['auth', 'role:' . Roles::USER . ',' . Roles::USER_CONFIRMED])->prefix('moderation')->name('moderation.')->group(function () {
        // Get report form with reasons for a specific topic (AJAX, returns HTML)
        Route::get('report-form/{topicKey}/{entityId}', [ModerationReportController::class, 'form'])
            ->name('report.form');

        // Submit a report (AJAX, returns JSON)
        Route::post('report', [ModerationReportController::class, 'store'])
            ->name('report.store');
    });
});
