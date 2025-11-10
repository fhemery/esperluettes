@section('title', __('story::chapters.create.title') . ' – ' . config('app.name'))

<x-app-layout size="md">
    <div class="w-full p-2 sm:p-4 overflow-hidden flex flex-col gap-4 surface-read text-on-surface">
        <x-shared::title>{{ __('story::chapters.create.heading', ['story' => $story->title]) }}</x-shared::title>

        <form method="POST" action="{{ route('chapters.store', ['storySlug' => $story->slug]) }}"
            class="flex flex-col gap-4">
            @csrf

            @include('story::chapters.partials.form')

            <div class="flex justify-end gap-4">
                <x-shared::button color="neutral" :outline="true" type="button" onclick="window.history.back()">{{ __('story::chapters.form.cancel') }}</x-shared::button>
                <x-shared::button color="accent" type="submit">{{ __('story::chapters.form.submit') }}</x-shared::button>
            </div>
        </form>
    </div>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush
</x-app-layout>