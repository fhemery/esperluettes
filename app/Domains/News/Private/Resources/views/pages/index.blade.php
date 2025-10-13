<x-app-layout :page="$page">
    <div class="w-full flex flex-col gap-6">
        <!-- News carousel -->
        <x-shared::title icon="news">{{ __('news::public.index.pinned_title') }}</x-shared::title>
        
        <div class="py-4 md:py-8 md:px-16 surface-read text-on-surface">
            <x-news::carousel :items="$pinned" />
        </div>

        <!-- All news -->
        <x-shared::title icon="news">{{ __('news::public.index.title') }}</x-shared::title>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @forelse($news as $news)
            <article class="overflow-hidden surface-read text-on-surface flex flex-col">
                @if($news->header_image_path)
                <a href="{{ route('news.show', $news->slug) }}" class="w-full">
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
                            loading="lazy">
                    </picture>
                </a>
                @else
                <div></div>
                @endif
                <div class="p-4 flex-1 flex flex-col gap-4">
                    <a href="{{ route('news.show', $news->slug) }}" class="hover:underline">
                        <x-shared::title class="text-secondary text-xl" tag="h2">
                            {{ $news->title }}
                        </x-shared::title>
                    </a>
                    <p class="text-sm" x-data="{updatedAt: '{{ $news->updated_at }}'}">{{__('news::public.index.updated_at')}}
                        <span x-text="DateUtils.formatDate(updatedAt)"></span>
                    </p>
                </div>
            </article>
            @empty
            <p class="text-fg">{{ __('news::public.index.empty') }}</p>
            @endforelse
        </div>
    </div>
</x-app-layout>