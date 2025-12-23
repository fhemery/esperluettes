@php
    use App\Domains\Calendar\Private\Activities\SecretGift\Models\SecretGiftAssignment;

    /** @var \App\Domains\Calendar\Private\Models\Activity $activity */
    /** @var SecretGiftAssignment $assignment */
    /** @var ?object $recipientProfile */
    /** @var ?string $recipientPreferences */
    /** @var bool $isActive */
@endphp

<div class="space-y-6">
    {{-- Recipient Info Card --}}
    <div class="surface-read p-6 rounded-lg">
        <h3 class="text-lg font-bold mb-4">{{ __('secret-gift::secret-gift.your_recipient') }}</h3>
        
        <div class="flex items-center gap-4 mb-6">
            @if($recipientProfile)
                <x-shared::avatar 
                    :url="$recipientProfile->avatar_url" 
                    :name="$recipientProfile->display_name" 
                    size="lg" 
                />
                <div>
                    <p class="font-semibold text-lg">{{ $recipientProfile->display_name }}</p>
                </div>
            @else
                <p class="text-fg/60">{{ __('secret-gift::secret-gift.unknown_user') }}</p>
            @endif
        </div>

        {{-- Recipient Preferences --}}
        @if($recipientPreferences)
            <div class="border-t border-border pt-4">
                <h4 class="font-semibold mb-2">{{ __('secret-gift::secret-gift.their_preferences') }}</h4>
                <div class="rich-content prose prose-sm max-w-none">
                    {!! $recipientPreferences !!}
                </div>
            </div>
        @else
            <p class="text-fg/60 italic">{{ __('secret-gift::secret-gift.no_preferences') }}</p>
        @endif
    </div>

    {{-- Gift Creation Form --}}
    <div class="surface-read p-6 rounded-lg">
        <h3 class="text-lg font-bold mb-4">{{ __('secret-gift::secret-gift.create_your_gift') }}</h3>

        @if(!$isActive)
            <div class="bg-warning/10 text-warning p-4 rounded-lg mb-4">
                <p>{{ __('secret-gift::secret-gift.activity_not_active') }}</p>
            </div>
        @endif

        <form 
            action="{{ route('secret-gift.save-gift', $activity) }}" 
            method="POST" 
            enctype="multipart/form-data"
            x-data="{ giftMode: '{{ $assignment->gift_text ? 'text' : ($assignment->gift_image_path ? 'image' : ($assignment->gift_sound_path ? 'sound' : 'text')) }}' }"
        >
            @csrf

            {{-- Mode Toggle --}}
            <div class="flex gap-2 mb-6">
                <button 
                    type="button" 
                    @click="giftMode = 'text'"
                    :class="giftMode === 'text' ? 'bg-primary text-on-primary' : 'surface-neutral'"
                    class="px-4 py-2 rounded-lg transition-colors"
                >
                    <span class="material-symbols-outlined align-middle mr-1">edit_note</span>
                    {{ __('secret-gift::secret-gift.mode_text') }}
                </button>
                <button 
                    type="button" 
                    @click="giftMode = 'image'"
                    :class="giftMode === 'image' ? 'bg-primary text-on-primary' : 'surface-neutral'"
                    class="px-4 py-2 rounded-lg transition-colors"
                >
                    <span class="material-symbols-outlined align-middle mr-1">image</span>
                    {{ __('secret-gift::secret-gift.mode_image') }}
                </button>
                <button 
                    type="button" 
                    @click="giftMode = 'sound'"
                    :class="giftMode === 'sound' ? 'bg-primary text-on-primary' : 'surface-neutral'"
                    class="px-4 py-2 rounded-lg transition-colors"
                >
                    <span class="material-symbols-outlined align-middle mr-1">audio_file</span>
                    {{ __('secret-gift::secret-gift.mode_sound') }}
                </button>
            </div>

            {{-- Text Editor --}}
            <div x-show="giftMode === 'text'" x-cloak class="mb-6">
                <x-shared::editor
                    id="gift-text-editor"
                    name="gift_text"
                    :defaultValue="old('gift_text', $assignment->gift_text ?? '')"
                    :nbLines="12"
                    :placeholder="__('secret-gift::secret-gift.text_placeholder')"
                />
            </div>

            {{-- Image Upload --}}
            <div x-show="giftMode === 'image'" x-cloak class="mb-6">
                <x-shared::image-upload
                    name="gift_image"
                    :label="__('secret-gift::secret-gift.upload_image')"
                    :currentUrl="$assignment->gift_image_path ? route('secret-gift.image', [$activity, $assignment]) : null"
                    :currentPath="$assignment->gift_image_path"
                    :maxSize="5120"
                    accept="image/jpeg,image/png"
                    :helpText="__('secret-gift::secret-gift.image_help')"
                />
            </div>

            {{-- Sound Upload --}}
            <div x-show="giftMode === 'sound'" x-cloak class="mb-6">
                <x-shared::sound-upload
                    name="gift_sound"
                    :label="__('secret-gift::secret-gift.upload_sound')"
                    :currentUrl="$assignment->gift_sound_path ? route('secret-gift.sound', [$activity, $assignment]) : null"
                    :currentPath="$assignment->gift_sound_path"
                    :maxSize="10240"
                    accept="audio/mp3"
                    :helpText="__('secret-gift::secret-gift.sound_help')"
                />
            </div>

            {{-- Submit --}}
            @if($isActive)
                <div class="flex justify-end">
                    <x-shared::button type="submit" color="primary">
                        <span class="material-symbols-outlined align-middle mr-1">save</span>
                        {{ __('secret-gift::secret-gift.save_gift') }}
                    </x-shared::button>
                </div>
            @endif
        </form>
    </div>
</div>
