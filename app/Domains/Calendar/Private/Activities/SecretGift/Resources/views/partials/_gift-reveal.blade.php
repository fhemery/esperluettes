@php
    use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;

    /** @var \App\Domains\Calendar\Private\Models\Activity $activity */
    /** @var SecretGiftAssignment $assignment */
    /** @var ?object $giverProfile */
@endphp

<div class="space-y-6">
    {{-- Giver Info --}}
    <div class="surface-read p-6 rounded-lg">
        <h3 class="text-lg font-bold mb-4">{{ __('secret-gift::secret-gift.gift_from') }}</h3>
        
        <div class="flex items-center gap-4">
            @if($giverProfile)
                <x-shared::avatar 
                    :url="$giverProfile->avatar_url" 
                    :name="$giverProfile->display_name" 
                    size="lg" 
                />
                <div>
                    <p class="font-semibold text-lg">{{ $giverProfile->display_name }}</p>
                </div>
            @else
                <p class="text-fg/60">{{ __('secret-gift::secret-gift.unknown_user') }}</p>
            @endif
        </div>
    </div>

    {{-- Gift Content --}}
    <div class="surface-read p-6 rounded-lg">
        <h3 class="text-lg font-bold mb-4">{{ __('secret-gift::secret-gift.your_gift') }}</h3>

        @if(!$assignment->hasGift())
            <div class="text-center py-8">
                <span class="material-symbols-outlined text-[48px] text-fg/40 mb-4">sentiment_dissatisfied</span>
                <p class="text-lg text-fg/60">{{ __('secret-gift::secret-gift.no_gift_received') }}</p>
            </div>
        @else
            {{-- Text Gift --}}
            @if($assignment->gift_text)
                <div class="rich-content prose prose-sm max-w-none mb-6">
                    {!! $assignment->gift_text !!}
                </div>
            @endif

            {{-- Image Gift --}}
            @if($assignment->gift_image_path)
                <div class="mt-4">
                    <img 
                        src="{{ route('secret-gift.image', [$activity, $assignment]) }}" 
                        alt="{{ __('secret-gift::secret-gift.gift_image') }}"
                        class="max-w-full rounded-lg shadow-lg"
                    />
                </div>
            @endif
        @endif
    </div>
</div>
