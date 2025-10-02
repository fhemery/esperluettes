@section('title', __('story::chapters.create.title') . ' – ' . config('app.name'))

<x-app-layout>
    <div class="max-w-4xl mx-auto w-full p-2 sm:p-4 overflow-hidden flex flex-col gap-4">
        <h1 class="font-semibold text-2xl text-accent">{{ __('story::chapters.create.heading', ['story' => $story->title]) }}</h1>

        <form method="POST" action="{{ route('chapters.store', ['storySlug' => $story->slug]) }}"
            class="flex flex-col gap-4">
            @csrf

            @include('story::chapters.partials.form')

            <div class="flex justify-end gap-4">
                <x-shared::button color="neutral" type="button" onclick="window.history.back()">{{ __('story::chapters.form.cancel') }}</x-shared::button>
                <x-shared::button color="accent" type="submit">{{ __('story::chapters.form.submit') }}</x-shared::button>
            </div>
        </form>
    </div>
</x-app-layout>