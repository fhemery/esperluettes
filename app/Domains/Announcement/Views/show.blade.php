<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <article>
            <header class="mb-6" style="max-width:800px; margin-left:auto; margin-right:auto;">
                <h1 class="text-3xl font-bold mb-2">{{ $announcement->title }}</h1>
                @if($announcement->published_at)
                    <p class="text-gray-500 text-sm">{{ $announcement->published_at->format('Y-m-d') }}</p>
                @endif
            </header>

            @if($announcement->header_image_path)
                <figure class="mb-6" style="max-width:800px; margin-left:auto; margin-right:auto;">
                    @php
                        $path = pathinfo($announcement->header_image_path ?? '', PATHINFO_DIRNAME);
                        $name = pathinfo($announcement->header_image_path ?? '', PATHINFO_FILENAME);
                    @endphp
                    <picture>
                        <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                        <img
                            class="max-w-full h-auto rounded mx-auto"
                            style="max-width:800px;"
                            src="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }}"
                            srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                            sizes="(max-width: 640px) 100vw, 800px"
                            alt="{{ $announcement->title }}"
                            loading="lazy"
                        >
                    </picture>
                </figure>
            @endif

            <style>
                /* Minimal content styles if Tailwind Typography isn't active */
                .announcement-content p { margin: 0.75rem 0; line-height: 1.75; }
                .announcement-content h2 { font-size: 1.5rem; font-weight: 700; margin: 1.5rem 0 0.75rem; }
                .announcement-content h3 { font-size: 1.25rem; font-weight: 700; margin: 1.25rem 0 0.5rem; }
                .announcement-content ul { list-style: disc; margin: 0.75rem 0 0.75rem 1.25rem; }
                .announcement-content ol { list-style: decimal; margin: 0.75rem 0 0.75rem 1.25rem; }
                .announcement-content a { color: #2563eb; text-decoration: underline; }
                .announcement-content blockquote { border-left: 4px solid #e5e7eb; padding-left: 1rem; color: #6b7280; margin: 1rem 0; }
                .announcement-content img { max-width: 100%; height: auto; }
            </style>
            <div class="announcement-content max-w-[800px] mx-auto">
                {!! $announcement->content !!}
            </div>
        </article>
    </div>
</x-app-layout>
