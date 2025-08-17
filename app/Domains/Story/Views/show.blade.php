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
                    <div class="flex items-center justify-between mb-6">

                    </div>

                    <article class="prose max-w-none">
                        <h2>{{ __('story::shared.description.label') }}</h2>
                        {!! $story->description !!}
                    </article>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>