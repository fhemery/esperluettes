@section('title', __('story::edit.title', ['title' => $story->title]))
<x-app-layout :page="$page" size="md">
    <div class="w-full mx-auto flex-1 overflow-hidden flex flex-col gap-4 surface-read text-on-surface p-2 sm:p-4">
        <x-shared::title>{{ __('story::edit.title', ['title' => $story->title]) }}</x-shared::title>

        <form action="{{ route('stories.update', ['slug' => $story->slug]) }}" method="POST" novalidate
            class="bg-transparent flex-1 flex flex-col gap-4">
            @csrf
            @method('PUT')

            <x-story::form :story="$story" :referentials="$referentials" />

            <div class="flex justify-end gap-4">
                <x-shared::button color="neutral" :outline="true" type="button" onclick="window.history.back()">
                    {{ __('story::edit.actions.cancel') }}
                </x-shared::button>
                <x-shared::button color="accent" type="submit">
                    {{ __('story::edit.actions.save') }}
                </x-shared::button>
            </div>
        </form>
    </div>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush
</x-app-layout>