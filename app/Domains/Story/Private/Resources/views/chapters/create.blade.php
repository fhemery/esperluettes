@section('title', __('story::chapters.create.title') . ' – ' . config('app.name'))

<x-app-layout>
    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="font-semibold text-2xl mb-6">{{ __('story::chapters.create.heading', ['story' => $story->title]) }}</h1>

                    <form method="POST" action="{{ route('chapters.store', ['storySlug' => $story->slug]) }}">
                        @csrf

                        @include('story::chapters.partials.form')

                        <div class="mt-8 flex justify-end gap-12">
                            <x-shared::button color="accent" type="submit">{{ __('story::chapters.form.submit') }}</x-shared::button>
                            <x-shared::button color="neutral" type="button" onclick="window.history.back()">{{ __('story::chapters.form.cancel') }}</x-shared::button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>