<?php

namespace App\Domains\Auth\Private\Requests;

use App\Domains\Auth\Private\Models\User;
use App\Domains\Auth\Private\Services\ActivationCodeService;
use App\Domains\Shared\Validation\Rules\UniqueProfileDisplayName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'min:2', 'max:255', new UniqueProfileDisplayName(null)],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_under_15' => ['boolean'],
            'accept_terms' => ['required', 'accepted'],
        ];

        if (config('app.require_activation_code', false)) {
            $rules['activation_code'] = [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    $activationCodeService = app(ActivationCodeService::class);
                    if (!$activationCodeService->isCodeValid($value)) {
                        $fail(__('auth::register.form.activation_code.invalid'));
                    }
                },
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('auth::register.form.name.required'),
            'name.string' => __('auth::register.form.name.string'),
            'name.min' => __('auth::register.form.name.min'),
            'name.max' => __('auth::register.form.name.max'),

            'email.required' => __('auth::register.form.email.required'),
            'email.string' => __('auth::register.form.email.string'),
            'email.lowercase' => __('auth::register.form.email.lowercase'),
            'email.email' => __('auth::register.form.email.email'),
            'email.max' => __('auth::register.form.email.max'),
            'email.unique' => __('auth::register.form.email.unique'),

            'password.required' => __('auth::register.form.password.required'),
            'password.confirmed' => __('auth::register.form.password.confirmed'),
            // When Password::defaults() enforces a minimum length
            'password.min' => __('auth::register.form.password.min'),
            // When Password rule enforces composition (if enabled)
            'password.letters' => __('auth::register.form.password.letters'),
            'password.mixed' => __('auth::register.form.password.mixed'),
            'password.numbers' => __('auth::register.form.password.numbers'),
            'password.symbols' => __('auth::register.form.password.symbols'),
            'password.uncompromised' => __('auth::register.form.password.uncompromised'),

            'accept_terms.required' => __('auth::shared.accept_terms.required'),
            'accept_terms.accepted' => __('auth::shared.accept_terms.required'),

            'activation_code.required' => __('auth::register.form.activation_code.required'),
            'activation_code.string' => __('auth::register.form.activation_code.string'),
            'activation_code.invalid' => __('auth::register.form.activation_code.invalid'),
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('Name'),
            'email' => __('Email'),
            'password' => __('Password'),
            'activation_code' => __('Activation code'),
        ];
    }
}
