<?php

use App\Domains\Auth\Controllers\AuthenticatedSessionController;
use App\Domains\Auth\Controllers\ConfirmablePasswordController;
use App\Domains\Auth\Controllers\EmailVerificationNotificationController;
use App\Domains\Auth\Controllers\EmailVerificationPromptController;
use App\Domains\Auth\Controllers\NewPasswordController;
use App\Domains\Auth\Controllers\PasswordController;
use App\Domains\Auth\Controllers\PasswordResetLinkController;
use App\Domains\Auth\Controllers\RegisteredUserController;
use App\Domains\Auth\Controllers\VerifyEmailController;
use App\Domains\Auth\Controllers\UserAccountController;
use Illuminate\Support\Facades\Route;

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
});
