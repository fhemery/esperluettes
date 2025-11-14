@section('title', __('story::chapters.edit.title', ['title' => $chapter->title]) . ' – ' . $story->title)

<x-app-layout :page="$page" size="md">
    <div class="w-full text-fg flex flex-col gap-4 surface-read text-on-surface p-2 sm:p-4">
        <x-shared::title>{{ __('story::chapters.edit.heading', ['story' => $story->title]) }}</x-shared::title>

        <form method="POST" action="{{ route('chapters.update', ['storySlug' => $story->slug, 'chapterSlug' => $chapter->slug]) }}"
            class="flex flex-col gap-4">
            @csrf
            @method('PUT')

            @include('story::chapters.partials.form')

            <div class="flex justify-end gap-4">
                <a href="{{ url('/stories/'.$story->slug.'/chapters/'.$chapter->slug) }}">
                    <x-shared::button color="neutral" :outline="true">{{ __('story::chapters.form.cancel') }}</x-shared::button>
                </a>
                <x-shared::button color="accent" type="submit">{{ __('story::chapters.form.update') }}</x-shared::button>
            </div>
        </form>
    </div>
    @push('scripts')
        @vite('app/Domains/Shared/Resources/js/editor-bundle.js')
    @endpush
</x-app-layout>