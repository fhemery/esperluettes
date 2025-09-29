<div class="flex flex-col justify-between gap-4 items-center surface-read text-on-surface p-4 h-full">
    <h3 class="flex items-center self-start gap-2 text-accent font-semibold text-xl">
        <span class="material-symbols-outlined">
            stylus_fountain_pen
        </span> 
        {{ __('story::keep-writing.title') }}
    </h3>

    @if($error)
    <p>{{ $error }}</p>
    @elseif(!$isAllowedToCreate)
        <p>{{ __('story::keep-writing.cannot_write') }}</p>
        <a href="{{ route('stories.index') }}">
            <x-shared::button color="accent">
                {{ __('story::keep-writing.go_to_stories') }}
            </x-shared::button>
        </a>
    @elseif(!$vm)
    <p>{{ __('story::keep-writing.empty') }}</p>
    <a href="{{ route('stories.create') }}">
        <x-shared::button color="accent">
            {{ __('story::keep-writing.new_story') }}
        </x-shared::button>
    </a>
    @else
    <x-story::card :item="$vm" :display-authors="false" />

    <div class="flex justify-center items-center gap-2 w-full ">
        <a href="{{ route('chapters.create', ['storySlug' => $vm->getSlug()]) }}">
            <x-shared::button color="accent" :disabled="!$hasCreditsLeft">
                {{ __('story::keep-writing.new_chapter') }}
            </x-shared::button>
        </a>
        @if(!$hasCreditsLeft)
        <x-shared::tooltip icon="info" placement="top" maxWidth="18rem">
                                <div class="text-sm text-fg">
                                    {{ __('story::chapters.no_chapter_credits_left') }}
                                </div>
                            </x-shared::tooltip>
        @endif
    </div>
    @endif
</div>