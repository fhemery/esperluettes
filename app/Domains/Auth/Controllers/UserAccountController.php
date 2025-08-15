<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Auth\Requests\UserAccountUpdateRequest;
use App\Domains\Auth\Events\UserNameUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Domains\Shared\Controllers\Controller;

class UserAccountController extends Controller
{
    /**
     * Display the user's Account form.
     */
    public function edit(Request $request): View
    {
        return view('auth::account.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's account information.
     */
    public function update(UserAccountUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $originalName = $request->user()->getOriginal('name');
        $request->user()->save();

        // Dispatch domain event if the user's name actually changed
        if ($request->user()->wasChanged('name')) {
            event(new UserNameUpdated(
                userId: $request->user()->id,
                oldName: (string) $originalName,
                newName: (string) $request->user()->name,
                changedAt: now(),
            ));
        }

        return Redirect::route('account.edit')->with('status', __('auth::account.account-updated'));
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
