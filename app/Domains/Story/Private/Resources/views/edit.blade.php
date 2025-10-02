@section('title', __('story::edit.title', ['title' => $story->title]))
<x-app-layout>
    <div class="max-w-4xl w-full mx-auto flex-1 text-fg overflow-hidden flex flex-col gap-4">
        <h2 class="font-semibold text-xl text-accent">
            {{ __('story::edit.title', ['title' => $story->title]) }}
        </h2>

        <form action="{{ route('stories.update', ['slug' => $story->slug]) }}" method="POST" novalidate
            class="bg-transparent flex-1 flex flex-col gap-4">
            @csrf
            @method('PUT')

            <x-story::form :story="$story" :referentials="$referentials" />

            <div class="flex justify-end gap-4">
                <x-shared::button color="neutral" type="button" onclick="window.history.back()">
                    {{ __('story::edit.actions.cancel') }}
                </x-shared::button>
                <x-shared::button color="accent" type="submit">
                    {{ __('story::edit.actions.save') }}
                </x-shared::button>
            </div>
        </form>
    </div>
</x-app-layout>