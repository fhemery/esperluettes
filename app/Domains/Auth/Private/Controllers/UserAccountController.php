<?php

namespace App\Domains\Auth\Private\Controllers;

use App\Domains\Auth\Private\Requests\UserAccountUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Domains\Shared\Controllers\Controller;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Public\Events\UserDeleted;

class UserAccountController extends Controller
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}
    /**
     * Display the user's Account form.
     */
    public function edit(Request $request): View
    {
        return view('auth::pages.account.edit', [
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

        $request->user()->save();

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

        // Emit deletion event
        $this->eventBus->emit(new UserDeleted(userId: (int) $user->id));

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
