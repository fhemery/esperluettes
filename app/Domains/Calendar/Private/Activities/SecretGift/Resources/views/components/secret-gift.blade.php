@php
    use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;
    use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftParticipant;

    /** @var \App\Domains\Calendar\Private\Models\Activity $activity */
    /** @var bool $isParticipant */
    /** @var ?SecretGiftParticipant $participant */
    /** @var bool $registrationOpen */
    /** @var \Illuminate\Support\Collection<int,\App\Domains\Shared\Dto\ProfileDto> $participants */
    /** @var bool $isActive */
    /** @var bool $isEnded */
    /** @var bool $isPreview */
    /** @var ?SecretGiftAssignment $assignmentAsGiver */
    /** @var ?SecretGiftAssignment $assignmentAsRecipient */
    /** @var ?object $recipientProfile */
    /** @var ?string $recipientPreferences */
    /** @var ?object $giverProfile */
@endphp

<div class="secret-gift-activity flex flex-col gap-6">
    @if($isPreview)
        {{-- Enrolment surface. The window is re-derived server-side by every write
             endpoint: hiding a control here is presentation, not authorization. --}}
        @if($isParticipant && $registrationOpen)
            <div class="surface-read p-6 rounded-lg flex flex-col gap-4">
                <h3 class="text-lg font-bold">{{ __('secret-gift::secret-gift.enrolment.preferences_label') }}</h3>
                <p class="text-sm text-fg/70">{{ __('secret-gift::secret-gift.enrolment.preferences_hint') }}</p>

                <form method="POST" action="{{ route('secret-gift.participants.update', $activity->id) }}"
                      class="flex flex-col gap-4">
                    @csrf
                    @method('PUT')

                    <x-editor::rich-text
                        id="sg_preferences"
                        name="preferences"
                        :defaultValue="old('preferences', $participant->preferences ?? '')"
                        :nbLines="10"
                    />

                    <div class="flex justify-end">
                        <x-shared::button type="submit" color="primary" icon="save">
                            {{ __('secret-gift::secret-gift.enrolment.save_preferences') }}
                        </x-shared::button>
                    </div>
                </form>

                <div class="border-t border-border pt-4 flex justify-end">
                    <button type="button" class="text-sm text-error underline"
                            x-data x-on:click="$dispatch('open-modal', 'sg-leave')">
                        {{ __('secret-gift::secret-gift.enrolment.leave_button') }}
                    </button>
                </div>

                <x-shared::confirm-modal
                    name="sg-leave"
                    :title="__('secret-gift::secret-gift.enrolment.leave_confirm_title')"
                    :body="__('secret-gift::secret-gift.enrolment.leave_confirm_body')"
                    :cancel="__('secret-gift::secret-gift.enrolment.leave_confirm_cancel')"
                    :confirm="__('secret-gift::secret-gift.enrolment.leave_confirm_confirm')"
                    :action="route('secret-gift.participants.destroy', $activity->id)"
                    method="DELETE"
                />
            </div>
        @elseif($isParticipant)
            <div class="surface-read p-6 rounded-lg flex flex-col gap-4">
                <p class="text-lg">{{ __('secret-gift::secret-gift.waiting_for_start') }}</p>
                <p class="text-sm text-fg/70">{{ __('secret-gift::secret-gift.enrolment.registration_closed_participant') }}</p>

                <div class="border-t border-border pt-4">
                    <h3 class="font-semibold mb-2">{{ __('secret-gift::secret-gift.enrolment.preferences_label') }}</h3>
                    @if($participant?->preferences)
                        <div class="rich-content prose prose-sm max-w-none">
                            {!! $participant->preferences !!}
                        </div>
                    @else
                        <p class="text-fg/60 italic">{{ __('secret-gift::secret-gift.no_preferences') }}</p>
                    @endif
                </div>
            </div>
        @elseif($registrationOpen)
            <div class="surface-read p-6 rounded-lg flex flex-col gap-4">
                <h3 class="text-lg font-bold">{{ __('secret-gift::secret-gift.enrolment.join_title') }}</h3>
                <p class="text-sm text-fg/70">{{ __('secret-gift::secret-gift.enrolment.preferences_hint') }}</p>

                <form method="POST" action="{{ route('secret-gift.participants.store', $activity->id) }}"
                      class="flex flex-col gap-4">
                    @csrf

                    <x-editor::rich-text
                        id="sg_preferences"
                        name="preferences"
                        :defaultValue="old('preferences', __('secret-gift::secret-gift.preferences_template'))"
                        :nbLines="10"
                    />

                    <div class="flex justify-end">
                        <x-shared::button type="submit" color="primary" icon="redeem">
                            {{ __('secret-gift::secret-gift.enrolment.join_button') }}
                        </x-shared::button>
                    </div>
                </form>
            </div>
        @else
            <div class="surface-read p-6 rounded-lg text-center">
                <p class="text-lg">{{ __('secret-gift::secret-gift.not_participant') }}</p>
                <p class="text-sm text-fg/70">{{ __('secret-gift::secret-gift.enrolment.registration_closed') }}</p>
            </div>
        @endif
    @elseif(!$isParticipant)
        <div class="surface-read p-6 rounded-lg text-center">
            <p class="text-lg">{{ __('secret-gift::secret-gift.not_participant') }}</p>
        </div>
    @elseif(!$assignmentAsGiver)
        <div class="surface-read p-6 rounded-lg text-center">
            <p class="text-lg">{{ __('secret-gift::secret-gift.no_assignment_yet') }}</p>
        </div>
    @else
        <x-shared::tabs
            :tabs="[
                ['key' => 'prepare', 'label' => __('secret-gift::secret-gift.tab_my_gift')],
                ['key' => 'received', 'label' => __('secret-gift::secret-gift.tab_received_gift')],
            ]"
            initial="prepare"
            color="primary"
            tracking
        >
            {{-- Tab: Prepare Gift --}}
            <div x-show="tab === 'prepare'" x-cloak class="mt-6" role="tabpanel" id="tabs-panel-prepare" aria-labelledby="tabs-tab-prepare">
                @include('secret-gift::partials._gift-preparation', [
                    'activity' => $activity,
                    'assignment' => $assignmentAsGiver,
                    'recipientProfile' => $recipientProfile,
                    'recipientPreferences' => $recipientPreferences,
                    'isActive' => $isActive,
                ])
            </div>

            {{-- Tab: Received Gift --}}
            <div x-show="tab === 'received'" x-cloak class="mt-6" role="tabpanel" id="tabs-panel-received" aria-labelledby="tabs-tab-received">
                @if($isEnded && $assignmentAsRecipient)
                    @include('secret-gift::partials._gift-reveal', [
                        'activity' => $activity,
                        'assignment' => $assignmentAsRecipient,
                        'giverProfile' => $giverProfile,
                    ])
                @else
                    <div class="surface-read p-6 rounded-lg text-center">
                        <span class="material-symbols-outlined text-[48px] text-fg/40 mb-4">redeem</span>
                        <p class="text-lg">{{ __('secret-gift::secret-gift.gift_will_be_revealed') }}</p>
                    </div>
                @endif
            </div>
        </x-shared::tabs>
    @endif

    {{-- Participant list: enrolled-only, display names only. Never carries
         preferences, nor any pairing information. --}}
    @if($isParticipant)
        <div class="surface-read p-6 rounded-lg flex flex-col gap-4 secret-gift-participants">
            <h3 class="text-lg font-bold">{{ __('secret-gift::secret-gift.enrolment.participants_title') }}</h3>

            @if($participants->count() <= 1)
                <p class="text-fg/70">{{ __('secret-gift::secret-gift.enrolment.participants_alone') }}</p>
            @else
                <ul class="flex flex-wrap gap-4">
                    @foreach($participants as $profile)
                        <li class="flex items-center gap-2">
                            <x-shared::avatar :url="$profile->avatar_url" :name="$profile->display_name" size="sm" />
                            <span>{{ $profile->display_name }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif
</div>
