<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Shared\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use App\Domains\Auth\Models\ActivationCode;

class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
        }

        if ($request->user()->markEmailAsVerified()) {
            $this->assignRoleUponVerification($request);

            event(new Verified($request->user()));
        }

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1')->with('success', __('verification.verified'));
    }

    /**
     * @param EmailVerificationRequest $request
     * @return void
     */
    public function assignRoleUponVerification(EmailVerificationRequest $request): void
    {
        $user = $request->user();
        $requireActivation = config('app.require_activation_code', false);
        $usedActivation = ActivationCode::where('used_by_user_id', $user->id)->exists();

        if (!$requireActivation) {
            // Feature disabled: confirmed by default
            if ($user->isOnProbation()) {
                $user->removeRole('user');
            }
            if (!$user->isConfirmed()) {
                $user->assignRole('user-confirmed');
            }
        } else {
            if ($usedActivation) {
                // Used a code: promote to confirmed
                if ($user->isOnProbation()) {
                    $user->removeRole('user');
                }
                if (!$user->isConfirmed()) {
                    $user->assignRole('user-confirmed');
                }
            } else {
                // No code used: keep as user only
                if ($user->isConfirmed()) {
                    $user->removeRole('user-confirmed');
                }
                if (!$user->isOnProbation()) {
                    $user->assignRole('user');
                }
            }
        }
    }
}
