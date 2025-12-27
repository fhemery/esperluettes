<x-app-layout>
    <article class=" max-w-[800px] mx-auto">
        <header class="mb-6 md:mb-10">
            <div class="flex gap-2 items-baseline">
            <x-shared::title>{{ $page->title }}</x-shared::title>
            @if(Auth::user() && Auth::user()->hasRole(['admin', 'tech-admin']))
            <a href="{{ route('static.admin.edit', $page) }}">
                <x-shared::button icon="edit" size="xs" color="accent" :outline="true">
                </x-shared::button>
            </a>
            @endif
</div>  

            @if($page->published_at)
            <p class="text-fg/80 text-sm">{{ $page->published_at->format('Y-m-d') }}</p>
            @endif
        </header>


        @if($page->status === 'draft')
        <x-shared::badge color="warning">
            {{ __('static::public.draft_preview') }}
        </x-shared::badge>
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
                    class="max-w-full h-auto mx-auto"
                    src="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }}"
                    srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                    sizes="(max-width: 640px) 100vw, 800px"
                    alt=""
                    loading="lazy">
            </picture>
        </figure>
        @endif

        <style>
            .static-content p {
                margin: 0.75rem 0;
                line-height: 1.75;
                text-align: justify;
            }

            .static-content h2 {
                font-size: 1.5rem;
                color: rgb(var(--color-accent));
                font-weight: 700;
                margin: 1.5rem 0 0.75rem;
            }

            .static-content h3 {
                font-size: 1.25rem;
                color: rgb(var(--color-accent));
                font-weight: 700;
                margin: 1.25rem 0 0.5rem;
            }

            .static-content ul {
                list-style: disc;
                margin: 0.75rem 0 0.75rem 1.25rem;
            }

            .static-content ol {
                list-style: decimal;
                margin: 0.75rem 0 0.75rem 1.25rem;
            }

            .static-content a {
                color: rgb(var(--color-accent));
                text-decoration: underline;
            }

            .static-content blockquote {
                border-left: 4px solid #e5e7eb;
                padding-left: 1rem;
                color: #6b7280;
                margin: 1rem 0;
            }

            .static-content img {
                max-width: 100%;
                height: auto;
            }
        </style>
        <div class="static-content">
            {!! $page->content !!}
        </div>
    </article>
</x-app-layout>