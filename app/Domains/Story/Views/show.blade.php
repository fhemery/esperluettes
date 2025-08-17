@section('title', $story->title . ' – ' . config('app.name'))
@push('meta')
    <meta name="description" content="{{ $metaDescription }}">
    <meta property="og:type" content="article">
    <meta property="og:title" content="{{ $story->title }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:image" content="{{ asset('images/story/default-cover.svg') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $story->title }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ asset('images/story/default-cover.svg') }}">
@endpush

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center">
            <h1 class="font-semibold text-xl text-gray-800 leading-tight mr-2">
                {{ $story->title }}
            </h1>
            @if($isAuthor)
            <a href="{{ url('/stories/'.$story->slug.'/edit') }}"
                class="text-indigo-600 hover:text-indigo-800 mr-2"
                aria-label="{{ __('story::show.edit') }}"
                title="{{ __('story::show.edit') }}">
                <span class="material-symbols-outlined">edit</span>
            </a>
            @endif
            <div class="text-sm text-gray-600">
                @php
                $label = match($story->visibility) {
                'public' => __('story::shared.visibility.options.public'),
                'community' => __('story::shared.visibility.options.community'),
                'private' => __('story::shared.visibility.options.private'),
                default => $story->visibility,
                };
                $badgeClasses = match($story->visibility) {
                'public' => 'bg-green-100 text-green-800 ring-green-600/20',
                'community' => 'bg-blue-100 text-blue-800 ring-blue-600/20',
                'private' => 'bg-orange-100 text-orange-800 ring-orange-600/20',
                default => 'bg-gray-100 text-gray-800 ring-gray-600/20',
                };
                @endphp
                <span class="ml-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $badgeClasses }}">{{ $label }}</span>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-6 text-sm text-gray-600">
                        <div>
                            <span class="font-medium">{{ $story->authors->pluck('name')->join(', ') }}</span>
                        </div>
                        <div>
                            @php($date = app()->getLocale() === 'fr' ? $story->created_at->format('d-m-Y') : $story->created_at->format('Y-m-d'))
                            <span>{{ $date }}</span>
                        </div>
                    </div>

                    <div class="flex gap-6 items-start">
                        <div class="shrink-0" aria-hidden="true">
                            <div class="w-[150px] h-[200px] rounded-lg border-4 border-[#ACE1AF] flex items-center justify-center bg-white">
                                <span class="text-[90px] leading-none font-serif text-[#ACE1AF] select-none">&amp;</span>
                            </div>
                        </div>
                        <article class="prose max-w-none">
                            <h2>{{ __('story::shared.description.label') }}</h2>
                            @php($plain = trim(strip_tags($story->description ?? '')))
                            @if($plain === '')
                                <p class="italic text-gray-500">{{ __('story::show.no_description') }}</p>
                            @else
                                {!! $story->description !!}
                            @endif
                        </article>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>