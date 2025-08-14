<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <article>
            <header class="mb-6" style="max-width:800px; margin-left:auto; margin-right:auto;">
                <h1 class="text-3xl font-bold mb-2">{{ $news->title }}</h1>
                @if($news->published_at)
                    <p class="text-gray-500 text-sm">{{ $news->published_at->format('Y-m-d') }}</p>
                @endif
            </header>

            @if($news->status === 'draft')
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm px-4 py-2 rounded max-w-[800px] mx-auto">
                    {{ __('news::public.draft_preview') }}
                </div>
            @endif

            @if($news->header_image_path)
                <figure class="mb-6" style="max-width:800px; margin-left:auto; margin-right:auto;">
                    @php
                        $path = pathinfo($news->header_image_path ?? '', PATHINFO_DIRNAME);
                        $name = pathinfo($news->header_image_path ?? '', PATHINFO_FILENAME);
                    @endphp
                    <picture>
                        <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                        <img
                            class="max-w-full h-auto rounded mx-auto"
                            style="max-width:800px;"
                            src="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }}"
                            srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                            sizes="(max-width: 640px) 100vw, 800px"
                            alt="{{ $news->title }}"
                            loading="lazy"
                        >
                    </picture>
                </figure>
            @endif

            <style>
                /* Minimal content styles if Tailwind Typography isn't active */
                .news-content p { margin: 0.75rem 0; line-height: 1.75; }
                .news-content h2 { font-size: 1.5rem; font-weight: 700; margin: 1.5rem 0 0.75rem; }
                .news-content h3 { font-size: 1.25rem; font-weight: 700; margin: 1.25rem 0 0.5rem; }
                .news-content ul { list-style: disc; margin: 0.75rem 0 0.75rem 1.25rem; }
                .news-content ol { list-style: decimal; margin: 0.75rem 0 0.75rem 1.25rem; }
                .news-content a { color: #2563eb; text-decoration: underline; }
                .news-content blockquote { border-left: 4px solid #e5e7eb; padding-left: 1rem; color: #6b7280; margin: 1rem 0; }
                .news-content img { max-width: 100%; height: auto; }
            </style>
            <div class="news-content max-w-[800px] mx-auto">
                {!! $news->content !!}
            </div>
        </article>
    </div>
</x-app-layout>
