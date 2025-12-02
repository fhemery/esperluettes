<?php

namespace App\Domains\Auth\Private\Controllers;

use App\Domains\Shared\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Public\Events\EmailVerified as EmailVerifiedEvent;
use App\Domains\Auth\Private\Services\RoleCacheService;
use App\Domains\Auth\Private\Services\RoleService;

class VerifyEmailController extends Controller
{
    public function __construct(
        private readonly EventBus $eventBus,
        private readonly RoleCacheService $roleCache,
        private readonly RoleService $roles,
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
            $this->roleCache->clearForUser($request->user()->id);
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

        // Under-15 users must have parental authorization before getting roles
        if ($user->needsParentalAuthorization()) {
            return;
        }

        $this->roles->assignRolesBasedOnActivationCode($user);
    }
}
