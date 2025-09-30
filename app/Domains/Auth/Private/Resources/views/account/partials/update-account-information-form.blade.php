<section>
    <header class="flex flex-col gap-2">
        <h2 class="text-xl font-semibold text-accent flex gap-2 items-center">
            <span class="material-symbols-outlined">
                mail
            </span>

            {{ __('auth::account.info.title') }}
        </h2>

        <p>
            {{ __('auth::account.info.description') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('account.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div class="flex flex-col gap-2">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div class="flex flex-col gap-2">
                <p class="text-sm mt-2">
                    {{ __('auth::account.info.unverified') }}

                    <x-shared::button type="submit" color="tertiary" form="send-verification">{{ __('auth::account.info.resend') }}</x-shared::button>
                </p>

                @if (session('status') === 'verification-link-sent')
                <p class="font-medium text-sm text-green-600">
                    {{ __('auth::account.info.link_sent') }}
                </p>
                @endif
            </div>
            @endif
        </div>

        <div class="flex items-center gap-2">
            <x-shared::button color="accent" type="submit">{{ __('Save') }}</x-shared::button>

            @if (session('status') === __('auth::account.account-updated'))
            <p
                x-data="{ show: true }"
                x-show="show"
                x-transition
                x-init="setTimeout(() => show = false, 2000)"
                class="text-sm">{{ __('auth::account.info.saved') }}</p>
            @endif
        </div>
    </form>
</section>