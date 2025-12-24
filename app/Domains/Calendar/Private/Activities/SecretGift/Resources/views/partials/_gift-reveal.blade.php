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
                    <div class="mt-3">
                        <a 
                            href="{{ route('secret-gift.download-image', [$activity, $assignment]) }}" 
                            class="inline-flex items-center gap-2 px-4 py-2 bg-primary text-on-primary rounded-lg hover:bg-primary/90 transition-colors"
                            download
                        >
                            <span class="material-symbols-outlined text-[20px]">download</span>
                            {{ __('secret-gift::secret-gift.download_image') }}
                        </a>
                    </div>
                </div>
            @endif

            {{-- Sound Gift --}}
            @if($assignment->gift_sound_path)
                <div class="mt-4">
                    <div class="bg-surface border border-border rounded-lg p-4 shadow-lg">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <span class="material-symbols-outlined text-[24px] text-primary">audio_file</span>
                                <span class="font-medium">{{ __('secret-gift::secret-gift.gift_sound') }}</span>
                            </div>
                            <a 
                                href="{{ route('secret-gift.download-sound', [$activity, $assignment]) }}" 
                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-primary text-on-primary rounded-lg hover:bg-primary/90 transition-colors text-sm"
                                download
                            >
                                <span class="material-symbols-outlined text-[18px]">download</span>
                                {{ __('secret-gift::secret-gift.download_sound') }}
                            </a>
                        </div>
                        <audio 
                            controls 
                            src="{{ route('secret-gift.sound', [$activity, $assignment]) }}"
                            class="w-full"
                            preload="metadata"
                        >
                            {{ __('secret-gift::secret-gift.browser_no_support') }}
                        </audio>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
