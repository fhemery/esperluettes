<?php

use App\Domains\Auth\Private\Controllers\AuthenticatedSessionController;
use App\Domains\Auth\Private\Controllers\Admin\AuthAdminUserController;
use App\Domains\Auth\Private\Controllers\Admin\PromotionRequestController;
use App\Domains\Auth\Private\Controllers\Admin\UserController;
use App\Domains\Auth\Private\Controllers\ComplianceController;
use App\Domains\Auth\Private\Controllers\ConfirmablePasswordController;
use App\Domains\Auth\Private\Controllers\EmailVerificationNotificationController;
use App\Domains\Auth\Private\Controllers\EmailVerificationPromptController;
use App\Domains\Auth\Private\Controllers\NewPasswordController;
use App\Domains\Auth\Private\Controllers\PasswordController;
use App\Domains\Auth\Private\Controllers\PasswordResetLinkController;
use App\Domains\Auth\Private\Controllers\RegisteredUserController;
use App\Domains\Auth\Private\Controllers\VerifyEmailController;
use App\Domains\Auth\Private\Controllers\UserAccountController;
use App\Domains\Auth\Private\Controllers\RoleLookupController;
use App\Domains\Auth\Public\Api\Roles;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('register', [RegisteredUserController::class, 'create'])
            ->name('register');

        Route::post('register', [RegisteredUserController::class, 'store']);

        Route::get('login', [AuthenticatedSessionController::class, 'create'])
            ->name('login');

        Route::post('login', [AuthenticatedSessionController::class, 'store']);

        Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
            ->name('password.request');

        Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
            ->name('password.email');

        Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
            ->name('password.reset');

        Route::post('reset-password', [NewPasswordController::class, 'store'])
            ->name('password.store');

        // Helper route to set intended URL before redirecting to login
        Route::get('/auth/login-intended', function (\Illuminate\Http\Request $request) {
            $redirect = $request->query('redirect', url('/'));
            session()->put('url.intended', $redirect);
            return redirect()->route('login');
        })->name('login.with_intended');
    });

    // Compliance routes (terms acceptance and parental authorization)
    Route::middleware('auth')->group(function () {
        Route::prefix('compliance')->name('compliance.')->group(function () {
            Route::get('terms', [ComplianceController::class, 'showTerms'])->name('terms.show');
            Route::post('terms', [ComplianceController::class, 'acceptTerms'])->name('terms.accept');
            Route::get('parental-authorization', [ComplianceController::class, 'showParentalAuthorization'])->name('parental.show');
            Route::post('parental-authorization', [ComplianceController::class, 'uploadParentalAuthorization'])->name('parental.upload');
        });
    });

    Route::middleware(['auth','compliant'])->group(function () {
        // Lightweight heartbeat to keep the session alive and reduce CSRF timeouts.
        Route::get('/session/heartbeat', function () {
            return response()->noContent();
        })->middleware(['throttle:120,1'])->name('session.heartbeat');

        // CSRF token refresh endpoint for clients to check all pages have same CSRF token
        Route::get('/auth/csrf-token', function () {
            // Return the current CSRF token for this session without forcing regeneration
            return response()->json(['token' => csrf_token()]);
        })->middleware(['throttle:120,1'])->name('session.csrf');

        Route::get('verify-email', EmailVerificationPromptController::class)
            ->name('verification.notice');

        Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
            ->middleware(['signed', 'throttle:6,1'])
            ->name('verification.verify');

        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('verification.send');

        Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
            ->name('password.confirm');

        Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');

        // User account related routes
        Route::get('/account', [UserAccountController::class, 'edit'])->name('account.edit');
        Route::patch('/account', [UserAccountController::class, 'update'])->name('account.update');
        Route::delete('/account', [UserAccountController::class, 'destroy'])->name('account.destroy');

        // Roles lookup endpoints
        Route::get('/auth/roles/lookup', [RoleLookupController::class, 'search'])
            ->middleware(['throttle:60,1'])
            ->name('auth.roles.lookup');
        Route::get('/auth/roles/by-slugs', [RoleLookupController::class, 'bySlugs'])
            ->middleware(['throttle:60,1'])
            ->name('auth.roles.by_slugs');

        // Admin user activation endpoints (web, not API)
        // Authorization (roles) is enforced inside AuthPublicApi methods.
        Route::middleware('role:'.Roles::ADMIN.', '.Roles::MODERATOR.', '.Roles::TECH_ADMIN)->prefix('auth/admin')->name('auth.admin.')->group(function () {
            Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])
                ->name('users.deactivate');

            Route::post('users/{user}/reactivate', [UserController::class, 'reactivate'])
                ->name('users.reactivate');
        });

        // Admin user management (custom admin system - replaces Filament UserResource)
        // Use /administration prefix to avoid conflict with Filament's /admin routes
        Route::middleware('role:'.Roles::ADMIN.','.Roles::TECH_ADMIN)
            ->prefix('admin/auth/users')
            ->name('auth.admin.users.')
            ->group(function () {
                Route::get('/', [UserController::class, 'index'])->name('index');
                Route::get('/export', [UserController::class, 'export'])->name('export');
                Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
                Route::put('/{user}', [UserController::class, 'update'])->name('update');
                Route::post('/{user}/promote', [UserController::class, 'promote'])->name('promote');
                Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
                Route::get('/{user}/download-authorization', [UserController::class, 'downloadAuthorization'])->name('download-authorization');
                Route::post('/{user}/clear-authorization', [UserController::class, 'clearAuthorization'])->name('clear-authorization');
            });

        // Admin promotion request management
        Route::middleware('role:'.Roles::ADMIN.','.Roles::TECH_ADMIN.','.Roles::MODERATOR)
            ->prefix('admin/auth/promotion-requests')
            ->name('auth.admin.promotion-requests.')
            ->group(function () {
                Route::get('/', [PromotionRequestController::class, 'index'])->name('index');
                Route::post('/{promotionRequest}/accept', [PromotionRequestController::class, 'accept'])->name('accept');
                Route::post('/{promotionRequest}/reject', [PromotionRequestController::class, 'reject'])->name('reject');
            });
    });
});
