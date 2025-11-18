<?php

use App\Domains\Auth\Private\Controllers\AuthenticatedSessionController;
use App\Domains\Auth\Private\Controllers\AuthAdminUserController;
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

    Route::middleware('auth')->group(function () {
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
            Route::post('users/{user}/deactivate', [AuthAdminUserController::class, 'deactivate'])
                ->name('users.deactivate');

            Route::post('users/{user}/reactivate', [AuthAdminUserController::class, 'reactivate'])
                ->name('users.reactivate');
        });
    });
});
