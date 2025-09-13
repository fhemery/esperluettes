<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Shared\Controllers\Controller;
use App\Domains\Auth\Requests\LoginRequest;
use App\Domains\Events\PublicApi\EventBus;
use App\Domains\Auth\Events\UserLoggedIn;
use App\Domains\Auth\Events\UserLoggedOut;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly EventBus $eventBus,
    ) {}

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth::login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Emit login event for auditing/analytics
        $userId = (int) Auth::id();
        if ($userId) {
            $this->eventBus->emit(new UserLoggedIn(userId: $userId));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = (int) Auth::id();
        if ($userId) {
            $this->eventBus->emit(new UserLoggedOut(userId: $userId));
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
