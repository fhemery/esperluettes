<?php

namespace App\Domains\Auth\Controllers;

use App\Domains\Shared\Controllers\Controller;
use App\Domains\Auth\Models\User;
use App\Domains\Auth\Services\ActivationCodeService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
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
    public function store(Request $request): RedirectResponse
    {
        $validationRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        // Add activation code validation if required
        if (config('app.require_activation_code', false)) {
            $validationRules['activation_code'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $activationCodeService = app(ActivationCodeService::class);
                    if (!$activationCodeService->isCodeValid($value)) {
                        $fail(__('The activation code is invalid, expired, or already used.'));
                    }
                },
            ];
        }

        $request->validate($validationRules);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Mark activation code as used if provided
        if (config('app.require_activation_code', false) && $request->activation_code) {
            $activationCodeService = app(ActivationCodeService::class);
            $activationCodeService->validateAndUseCode($request->activation_code, $user);
        }

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
