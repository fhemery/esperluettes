<div class="flex flex-col justify-between gap-4 items-center surface-read text-on-surface p-4 h-full">
    <h3 class="flex items-center self-start gap-2 text-accent font-semibold text-xl">
        <span class="material-symbols-outlined">
            stylus_fountain_pen
        </span> 
        {{ __('story::keep-writing.title') }}
    </h3>

    @if(!$vm)
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
            <x-shared::button color="accent">
                {{ __('story::keep-writing.new_chapter') }}
            </x-shared::button>
        </a>
    </div>
    @endif
</div>