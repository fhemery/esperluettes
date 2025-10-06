<x-app-layout :page="$page">
    <div class="container mx-auto px-4 py-8">
        <x-shared::title icon="news">{{ __('news::public.index.title') }}</x-shared::title>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($news as $news)
                <article class="border rounded-lg overflow-hidden shadow-sm bg-white">
                    @if($news->header_image_path)
                        <a href="{{ route('news.show', $news->slug) }}">
                            @php
                                $path = pathinfo($news->header_image_path ?? '', PATHINFO_DIRNAME);
                                $name = pathinfo($news->header_image_path ?? '', PATHINFO_FILENAME);
                            @endphp
                            <picture>
                                <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                                <img
                                    class="w-full h-40 object-cover"
                                    src="{{ asset('storage/'.$news->header_image_path) }}"
                                    srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                                    sizes="(max-width: 640px) 100vw, 400px"
                                    alt="{{ $news->title }}"
                                    loading="lazy"
                                >
                            </picture>
                        </a>
                    @endif
                    <div class="p-4">
                        <h2 class="text-xl font-bold mb-2">
                            <a href="{{ route('news.show', $news->slug) }}" class="hover:underline">
                                {{ $news->title }}
                            </a>
                        </h2>
                        <p class="text-gray-600 mb-4">{{ $news->summary }}</p>
                        <a class="text-primary-600 hover:underline" href="{{ route('news.show', $news->slug) }}">
                            {{ __('news::public.index.read_more') }}
                        </a>
                    </div>
                </article>
            @empty
                <p class="text-gray-600">{{ __('news::public.index.empty') }}</p>
            @endforelse
        </div>
    </div>
</x-app-layout>
