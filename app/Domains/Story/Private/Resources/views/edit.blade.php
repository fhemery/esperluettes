@section('title', __('story::edit.title', ['title' => $story->title]))
<x-app-layout>
    <div class="bg-transparent">
        <div class="p-6 text-gray-900">
            <div class="max-w-3xl mx-auto">
                <div class="my-2">
                    <x-shared::button color="neutral" onclick="window.history.back()">
                        {{ __('shared::actions.back') }}
                    </x-shared::button>
                </div>
                <form action="{{ route('stories.update', ['slug' => $story->slug]) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <x-story::form :story="$story" :referentials="$referentials" />

                    <div class="mt-6 flex justify-center gap-12">
                        <x-shared::button color="accent" type="submit">
                            {{ __('story::edit.actions.save') }}
                        </x-shared::button>
                        <x-shared::button color="neutral" type="button" onclick="window.history.back()">
                            {{ __('story::edit.actions.cancel') }}
                        </x-shared::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>