<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <article class="prose max-w-none">
            <header class="mb-6">
                <h1 class="text-3xl font-bold mb-2">{{ $announcement->title }}</h1>
                @if($announcement->published_at)
                    <p class="text-gray-500 text-sm">{{ $announcement->published_at->format('Y-m-d') }}</p>
                @endif
            </header>

            @if($announcement->header_image_path)
                <figure class="mb-6">
                    @php
                        $path = pathinfo($announcement->header_image_path ?? '', PATHINFO_DIRNAME);
                        $name = pathinfo($announcement->header_image_path ?? '', PATHINFO_FILENAME);
                    @endphp
                    <picture>
                        <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                        <img
                            class="w-full h-auto rounded"
                            src="{{ asset('storage/'.$announcement->header_image_path) }}"
                            srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                            sizes="(max-width: 640px) 100vw, 800px"
                            alt="{{ $announcement->title }}"
                            loading="lazy"
                        >
                    </picture>
                </figure>
            @endif

            <div class="content">
                {!! $announcement->content !!}
            </div>
        </article>
    </div>
</x-app-layout>