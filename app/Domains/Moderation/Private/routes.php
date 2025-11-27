<?php

use App\Domains\Auth\Public\Api\Roles;
use App\Domains\Moderation\Private\Controllers\ModerationAdminController;
use App\Domains\Moderation\Private\Controllers\ModerationReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'compliant'])->group(function () {
    // Moderation routes - require authentication and verified email
    Route::middleware(['role:' . Roles::USER . ',' . Roles::USER_CONFIRMED])->prefix('moderation')->name('moderation.')->group(function () {
        // Get report form with reasons for a specific topic (AJAX, returns HTML)
        Route::get('report-form/{topicKey}/{entityId}', [ModerationReportController::class, 'form'])
            ->name('report.form');

        // Submit a report (AJAX, returns JSON)
        Route::post('report', [ModerationReportController::class, 'store'])
            ->name('report.store');
    });

    Route::middleware(['role:' . Roles::MODERATOR . ',' . Roles::ADMIN. ','.Roles::TECH_ADMIN])->prefix('admin/moderation')->name('moderation.admin.')->group(function () {
        Route::get('user-management', [ModerationAdminController::class, 'userManagementPage'])
            ->name('user-management');
        // Get report form with reasons for a specific topic (AJAX, returns HTML)
        Route::get('search', [ModerationAdminController::class, 'search'])
            ->name('search');
    });
});
