<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <article>
            <header class="mb-6 md:mb-10" style="max-width:800px; margin-left:auto; margin-right:auto;">
                <h1 class="text-3xl text-accent font-bold mb-2">{{ $page->title }}</h1>
                @if($page->published_at)
                    <p class="text-gray-500 text-sm">{{ $page->published_at->format('Y-m-d') }}</p>
                @endif
            </header>

            @if($page->status === 'draft')
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 text-sm px-4 py-2 rounded max-w-[800px] mx-auto">
                    {{ __('static::public.draft_preview') }}
                </div>
            @endif

            @if($page->header_image_path)
                <figure class="mb-6" style="max-width:800px; margin-left:auto; margin-right:auto;">
                    @php
                        $path = pathinfo($page->header_image_path ?? '', PATHINFO_DIRNAME);
                        $name = pathinfo($page->header_image_path ?? '', PATHINFO_FILENAME);
                    @endphp
                    <picture>
                        <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                        <img
                            class="max-w-full h-auto rounded mx-auto"
                            style="max-width:800px;"
                            src="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }}"
                            srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                            sizes="(max-width: 640px) 100vw, 800px"
                            alt="{{ $page->title }}"
                            loading="lazy"
                        >
                    </picture>
                </figure>
            @endif

            <style>
                .static-content p { margin: 0.75rem 0; line-height: 1.75; }
                .static-content h2 { font-size: 1.5rem; color: rgb(var(--color-accent)); font-weight: 700; margin: 1.5rem 0 0.75rem; }
                .static-content h3 { font-size: 1.25rem; color: rgb(var(--color-accent)); font-weight: 700; margin: 1.25rem 0 0.5rem; }
                .static-content ul { list-style: disc; margin: 0.75rem 0 0.75rem 1.25rem; }
                .static-content ol { list-style: decimal; margin: 0.75rem 0 0.75rem 1.25rem; }
                .static-content a { color: rgb(var(--color-accent)); text-decoration: underline; }
                .static-content blockquote { border-left: 4px solid #e5e7eb; padding-left: 1rem; color: #6b7280; margin: 1rem 0; }
                .static-content img { max-width: 100%; height: auto; }
            </style>
            <div class="static-content max-w-[800px] mx-auto">
                {!! $page->content !!}
            </div>
        </article>
    </div>
</x-app-layout>
