@section('title', __("story::seo.index.title") . ' – ' . config('app.name'))
@push('meta')
    <meta name="description" content="{{ __("story::seo.index.description") }}">
    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ __("story::seo.index.title") }}">
    <meta property="og:description" content="{{ __("story::seo.index.description") }}">
    <meta property="og:image" content="{{ asset('images/story/default-cover.svg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ __("story::seo.index.title") }}">
    <meta name="twitter:description" content="{{ __("story::seo.index.description") }}">
    <meta name="twitter:image" content="{{ asset('images/story/default-cover.svg') }}">
@endpush

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('story::index.heading') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if ($viewModel->isEmpty())
                        <div class="text-center text-gray-600 py-16">
                            {{ __('story::index.empty') }}
                        </div>
                    @else
                        <x-story.list-grid :view-model="$viewModel" />
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
