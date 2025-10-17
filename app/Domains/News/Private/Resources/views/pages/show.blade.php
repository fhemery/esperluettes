<x-app-layout :page="$page">
    <article class=" max-w-[800px] mx-auto">
        <header class="mb-6 md:mb-10">
            <x-shared::title>{{ $news->title }}</x-shared::title>
            @if($news->published_at)
            <p class="text-fg/80 text-sm">{{ $news->published_at->format('Y-m-d') }}</p>
            @endif
        </header>

        @if($news->status === 'draft')
        <x-shared::badge color="warning">
            {{ __('news::public.draft_preview') }}
        </x-shared::badge>
        @endif

        @if($news->header_image_path)
        <figure class="mb-6 w-full">
            @php
            $path = pathinfo($news->header_image_path ?? '', PATHINFO_DIRNAME);
            $name = pathinfo($news->header_image_path ?? '', PATHINFO_FILENAME);
            @endphp
            <picture>
                <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                <img
                    class="w-full h-auto"
                    src="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }}"
                    srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                    sizes="(max-width: 640px) calc(100vw - 2rem), 800px"
                    alt="{{ $news->title }}"
                    loading="lazy">
            </picture>
        </figure>
        @endif

        <style>
            /* Minimal content styles if Tailwind Typography isn't active */
            .news-content p {
                margin: 0.75rem 0;
                line-height: 1.75;
                text-align: justify;
            }

            .news-content h2 {
                font-size: 1.5rem;
                color: rgb(var(--color-accent));
                font-weight: 700;
                margin: 1.5rem 0 0.75rem;
            }

            .news-content h3 {
                font-size: 1.25rem;
                color: rgb(var(--color-accent));
                font-weight: 700;
                margin: 1.25rem 0 0.5rem;
            }

            .news-content ul {
                list-style: disc;
                margin: 0.75rem 0 0.75rem 1.25rem;
            }

            .news-content ol {
                list-style: decimal;
                margin: 0.75rem 0 0.75rem 1.25rem;
            }

            .news-content a {
                color: rgb(var(--color-accent));
                text-decoration: underline;
            }

            .news-content blockquote {
                border-left: 4px solid #e5e7eb;
                padding-left: 1rem;
                color: #6b7280;
                margin: 1rem 0;
            }

            .news-content img {
                max-width: 100%;
                height: auto;
            }
        </style>
        <div class="news-content">
            {!! $news->content !!}
        </div>
    </article>
</x-app-layout>