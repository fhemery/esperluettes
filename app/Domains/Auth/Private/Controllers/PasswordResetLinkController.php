<?php

namespace App\Domains\Auth\Private\Controllers;

use App\Domains\Shared\Controllers\Controller;
use App\Domains\Events\Public\Api\EventBus;
use App\Domains\Auth\Public\Events\PasswordResetRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}

    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth::pages.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            // Try to resolve user id if present for auditing
            $userId = \App\Domains\Auth\Private\Models\User::where('email', $request->string('email'))
                ->value('id');

            $this->eventBus->emit(new PasswordResetRequested(
                email: (string) $request->string('email'),
                userId: $userId ? (int) $userId : null,
            ));

            return back()->with('status', __($status));
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
