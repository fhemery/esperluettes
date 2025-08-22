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
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            @foreach ($viewModel->getItems() as $item)
                                <div class="group block bg-white rounded shadow hover:shadow-md transition overflow-hidden">
                                    <a href="{{ url('/stories/' . $item->getSlug()) }}" class="block">
                                        <div class="w-[150px] h-[200px] mx-auto overflow-hidden">
                                            <img
                                                src="{{ asset('images/story/default-cover.svg') }}"
                                                alt="{{ $item->getTitle() }}"
                                                class="w-[150px] h-[200px] object-contain group-hover:scale-105 transition"
                                            >
                                        </div>
                                        <div class="p-4 pt-2 pb-2">
                                            <div class="flex items-start justify-between gap-2">
                                                <h2 class="font-semibold text-gray-900 line-clamp-2">{{ $item->getTitle() }}</h2>
                                                <x-shared::tooltip type="info" :title="__('story::shared.description.label')" placement="right">
                                                    {{ strip_tags($item->getDescription()) }}
                                                </x-shared::tooltip>
                                            </div>
                                        </div>
                                    </a>
                                    <div class="p-4 pt-0 text-sm text-gray-600">
                                        <span class="font-medium">{{ __('story::shared.by') }} </span>
                                        <x-profile.inline-names :profiles="$item->getAuthors()" />
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-8">
                            {!! $viewModel->links() !!}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
