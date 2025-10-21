<?php

namespace App\Domains\Auth\Private\Controllers;

use App\Domains\Auth\Private\Requests\UserAccountUpdateRequest;
use App\Domains\Auth\Private\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use App\Domains\Shared\Controllers\Controller;
use App\Domains\Events\Public\Api\EventBus;

class UserAccountController extends Controller
{
    public function __construct(
        private readonly EventBus $eventBus,
        private readonly UserService $userService,
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

        $this->userService->deleteUser($request->user(), $request);

        return Redirect::to('/');
    }
}
