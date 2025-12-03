<?php

namespace App\Domains\Auth\Private\Controllers;

use App\Domains\Shared\Controllers\Controller;
use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Public\Events\UserRegistered;
use App\Domains\Auth\Private\Services\ActivationCodeService;
use App\Domains\Auth\Private\Requests\RegisterRequest;
use App\Domains\Auth\Public\Support\AuthConfigKeys;
use App\Domains\Config\Public\Api\ConfigPublicApi;
use App\Domains\Events\Public\Api\EventBus;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $configApi = app(ConfigPublicApi::class);
        $requireActivationCode = (bool) $configApi->getParameterValue(
            AuthConfigKeys::REQUIRE_ACTIVATION_CODE,
            AuthConfigKeys::DOMAIN
        );

        return view('auth::pages.register', compact('requireActivationCode'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'] ?? null,
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'is_under_15' => $data['is_under_15'] ?? false,
            'terms_accepted_at' => now(), // Terms are accepted if validation passes (checkbox checked)
        ]);

        // Mark activation code as used if provided (regardless of whether required)
        // This ensures sponsored users get USER_CONFIRMED role even when code is optional
        if ($data['activation_code'] ?? null) {
            $activationCodeService = app(ActivationCodeService::class);
            $activationCodeService->validateAndUseCode($data['activation_code'], $user);
        }

        // This event is internal to the Breeze framework, we keep it in case it is used
        // but will rely on our own domain events.
        event(new Registered($user));
        // Emit domain event for cross-domain consumers (e.g., Profile)
        app(EventBus::class)->emitSync(new UserRegistered(userId: $user->id, displayName: $data['name'] ?? null));

        Auth::login($user);

        // Redirect underage users to parental authorization if needed
        if ($user->needsParentalAuthorization()) {
            return redirect(route('compliance.parental.show', absolute: false));
        }

        return redirect(route('dashboard', absolute: false));
    }
}
