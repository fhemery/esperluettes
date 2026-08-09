@props(['activity' => null])

@php
    $configService = app(\App\Domains\Calendar\Private\Activities\SecretGift\Services\SecretGiftConfigService::class);

    // The create form renders this panel with no activity yet.
    $settings = $activity ? $configService->settingsFor($activity->id) : null;

    $registrationEndsAt = old(
        'secret_gift.registration_ends_at',
        $settings?->registration_ends_at?->format('Y-m-d\TH:i') ?? '',
    );

    // Shuffle panel data — only meaningful once the activity exists.
    if ($activity) {
        $participantCount = $configService->participantCount($activity->id);
        $participants = $configService->participantsWithProfiles($activity->id);
        $hasBeenShuffled = $configService->hasBeenShuffled($activity);
        $stateAllowsShuffle = $configService->isShuffleAllowedInState($activity);
        $shuffleBlockedReason = match (true) {
            ! $stateAllowsShuffle => __('secret-gift::secret-gift.config.shuffle_disabled_active'),
            $participantCount < 2 => __('secret-gift::secret-gift.config.shuffle_disabled_not_enough'),
            default => null,
        };
    }
@endphp

<div class="surface-bg p-6 rounded-lg flex flex-col gap-4 secret-gift-config" data-testid="sg-config-panel">
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

@if($activity)
    {{--
      The shuffle needs its own <form>, and this panel renders *inside* the
      activity form. Nested forms are illegal HTML — the browser silently drops
      the inner one and its button submits the activity instead. So the block is
      pushed to the stack both admin pages render after </form>.
    --}}
    @push('activity-config-extras')
        <div class="surface-bg p-6 rounded-lg flex flex-col gap-4 max-w-3xl secret-gift-shuffle" data-testid="sg-shuffle-panel">
            <h2 class="text-base font-semibold">{{ __('secret-gift::secret-gift.config.shuffle_title') }}</h2>

            @if($participantCount === 0)
                <p class="text-sm text-fg/70">{{ __('secret-gift::secret-gift.config.participants_empty') }}</p>
            @else
                <p class="text-sm text-fg/70">
                    {{ __('secret-gift::secret-gift.config.participants_count', ['count' => $participantCount]) }}
                </p>

                {{-- Display names only: `participantsWithProfiles()` never carries
                     preferences, which belong to the assigned giver alone. --}}
                <ul class="flex flex-wrap gap-4">
                    @foreach($participants as $profile)
                        <li class="flex items-center gap-2">
                            <x-shared::avatar :url="$profile->avatar_url" :name="$profile->display_name" size="sm" />
                            <span class="text-sm">{{ $profile->display_name }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            <p class="text-sm text-fg/70">
                {{ $hasBeenShuffled
                    ? __('secret-gift::secret-gift.config.already_shuffled')
                    : __('secret-gift::secret-gift.config.not_shuffled_yet') }}
            </p>

            @if($shuffleBlockedReason)
                <p class="text-sm text-error" data-testid="sg-shuffle-blocked">{{ $shuffleBlockedReason }}</p>
            @endif

            <div class="flex justify-end">
                <x-shared::button type="button" color="error" icon="shuffle" data-testid="sg-shuffle-button"
                    :disabled="$shuffleBlockedReason !== null"
                    x-on:click="$dispatch('open-modal', 'sg-shuffle')">
                    {{ __('secret-gift::secret-gift.config.shuffle_button') }}
                </x-shared::button>
            </div>

            <x-shared::confirm-modal
                name="sg-shuffle"
                :title="__('secret-gift::secret-gift.config.shuffle_confirm_title')"
                :body="__('secret-gift::secret-gift.config.shuffle_confirm_body')"
                :cancel="__('secret-gift::secret-gift.config.shuffle_confirm_cancel')"
                :confirm="__('secret-gift::secret-gift.config.shuffle_confirm_confirm')"
                :action="route('calendar.admin.secret-gift.shuffle', $activity->id)"
                method="POST"
            />
        </div>
    @endpush
@endif
