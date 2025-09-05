<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Shared\Controllers\Controller;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Events\UserRegistered;
use App\Domains\Auth\Services\ActivationCodeService;
use App\Domains\Auth\Requests\RegisterRequest;
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
        return view('auth::register');
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
        ]);

        // Mark activation code as used if provided
        if (config('app.require_activation_code', false) && ($data['activation_code'] ?? null)) {
            $activationCodeService = app(ActivationCodeService::class);
            $activationCodeService->validateAndUseCode($data['activation_code'], $user);
        }

        // This event is internal to the Breeze framework, we keep it in case it is used
        // but will rely on our own domain events.
        event(new Registered($user));
        // Emit domain event for cross-domain consumers (e.g., Profile)
        event(new UserRegistered(userId: $user->id, name: $data['name'] ?? null, registeredAt: now()));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
