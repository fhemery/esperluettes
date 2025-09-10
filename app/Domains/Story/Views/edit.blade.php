<x-app-layout>
    <div class="bg-transparent">
        <div class="p-6 text-gray-900">
            <div class="max-w-3xl mx-auto">
                <form action="{{ route('stories.update', ['slug' => $story->slug]) }}" method="POST" novalidate>
                    @csrf
                    @method('PUT')

                    <x-story::form :story="$story" :referentials="$referentials" />

                    <div class="mt-6 flex justify-center">
                        <x-shared::button color="accent" type="submit">
                            {{ __('story::edit.actions.save') }}
                        </x-shared::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>