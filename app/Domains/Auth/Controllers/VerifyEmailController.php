<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Shared\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use App\Domains\Auth\Models\ActivationCode;
use App\Domains\Auth\PublicApi\Roles;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Auth\Events\EmailVerified as EmailVerifiedEvent;
use App\Domains\Shared\Contracts\ProfilePublicApi as ProfilePublicApiContract;

class VerifyEmailController extends Controller
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}

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

            // Emit domain event after verification and role assignment
            $this->eventBus->emit(new EmailVerifiedEvent(userId: (int) $request->user()->id));
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
                $user->removeRole(Roles::USER);
            }
            if (!$user->isConfirmed()) {
                $user->assignRole(Roles::USER_CONFIRMED);
            }
        } else {
            if ($usedActivation) {
                // Used a code: promote to confirmed
                if ($user->isOnProbation()) {
                    $user->removeRole(Roles::USER);
                }
                if (!$user->isConfirmed()) {
                    $user->assignRole(Roles::USER_CONFIRMED);
                }
            } else {
                // No code used: keep as user only
                if ($user->isConfirmed()) {
                    $user->removeRole(Roles::USER_CONFIRMED);
                }
                if (!$user->isOnProbation()) {
                    $user->assignRole(Roles::USER);
                }
            }
        }
    }
}
