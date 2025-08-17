<x-app-layout>
    <x-slot name="header">
        <h1 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $story->title }}
        </h1>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('status'))
                        <div class="mb-4 text-green-700 bg-green-50 border border-green-200 px-4 py-2 rounded">
                            {{ session('status') }}
                        </div>
                    @endif

                    <div class="flex items-center justify-between mb-6">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">{{ __('story::show.visibility') }}:</span>
                            @php
                                $label = match($story->visibility) {
                                    'public' => __('story::create.form.visibility.options.public'),
                                    'community' => __('story::create.form.visibility.options.community'),
                                    'private' => __('story::create.form.visibility.options.private'),
                                    default => $story->visibility,
                                };
                            @endphp
                            <span>{{ $label }}</span>
                        </div>
                        @if($isAuthor)
                            <a href="{{ url('/stories/'.$story->slug.'/edit') }}" class="text-indigo-600 hover:text-indigo-800">
                                {{ __('story::show.edit') }}
                            </a>
                        @endif
                    </div>

                    <article class="prose max-w-none">
                        {!! $story->description !!}
                    </article>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
