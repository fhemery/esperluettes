@php
    use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;

    /** @var \App\Domains\Calendar\Private\Models\Activity $activity */
    /** @var bool $isParticipant */
    /** @var bool $isActive */
    /** @var bool $isEnded */
    /** @var bool $isPreview */
    /** @var ?SecretGiftAssignment $assignmentAsGiver */
    /** @var ?SecretGiftAssignment $assignmentAsRecipient */
    /** @var ?object $recipientProfile */
    /** @var ?string $recipientPreferences */
    /** @var ?object $giverProfile */
@endphp

@push('scripts')
    @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
@endpush

<div class="secret-gift-activity">
    @if(!$isParticipant)
        <div class="surface-read p-6 rounded-lg text-center">
            <p class="text-lg">{{ __('secret-gift::secret-gift.not_participant') }}</p>
        </div>
    @elseif($isPreview)
        <div class="surface-read p-6 rounded-lg text-center">
            <p class="text-lg">{{ __('secret-gift::secret-gift.waiting_for_start') }}</p>
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
            <div x-show="tab === 'prepare'" x-cloak class="mt-6">
                @include('secret-gift::partials._gift-preparation', [
                    'activity' => $activity,
                    'assignment' => $assignmentAsGiver,
                    'recipientProfile' => $recipientProfile,
                    'recipientPreferences' => $recipientPreferences,
                    'isActive' => $isActive,
                ])
            </div>

            {{-- Tab: Received Gift --}}
            <div x-show="tab === 'received'" x-cloak class="mt-6">
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
</div>
