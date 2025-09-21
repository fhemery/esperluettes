<?php

namespace App\Domains\Auth\Private\Controllers;

use App\Domains\Shared\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use App\Domains\Auth\Private\Services\PasswordService;

class PasswordController extends Controller
{

    public function __construct(
        private readonly PasswordService $passwordService,
    ) {}
    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Delegate to domain service which emits Auth.PasswordChanged
        $this->passwordService->changePassword($request->user(), $validated['password']);

        return back()->with('success', __('auth::account.password.updated'));
    }
}
