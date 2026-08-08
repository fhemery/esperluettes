@props(['activity' => null])

@php
    $configService = app(\App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftConfigService::class);

    // The create form renders this panel with no activity yet.
    $settings = $activity ? $configService->settingsFor($activity->id) : null;

    $registrationEndsAt = old(
        'secret_gift.registration_ends_at',
        $settings?->registration_ends_at?->format('Y-m-d\TH:i') ?? '',
    );
@endphp

<div class="surface-bg p-6 rounded-lg flex flex-col gap-4 secret-gift-config">
    <h2 class="text-base font-semibold">{{ __('secret-gift::secret-gift.config.section_title') }}</h2>
    <p class="text-xs text-fg/60">{{ __('secret-gift::secret-gift.config.registration_hint') }}</p>

    <div class="max-w-sm">
        <x-shared::input-label for="sg_registration_ends_at" :required="true">
            {{ __('secret-gift::secret-gift.config.registration_ends_at') }}
        </x-shared::input-label>
        <x-shared::datetime-local-input id="sg_registration_ends_at" name="secret_gift[registration_ends_at]"
            class="mt-1 block w-full" :value="$registrationEndsAt" />
        <x-shared::input-error :messages="$errors->get('secret_gift.registration_ends_at')" class="mt-1" />
    </div>
</div>
