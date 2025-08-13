<div
    x-data="{
        index: 0,
        count: {{ count($items) }},
        timer: null,
        start() {
            if (this.count < 2) return;
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            this.stop();
            this.timer = setInterval(() => {
                // Debug: comment out in production if noisy
                // console.debug('carousel next()', this.index, '->', (this.index + 1) % this.count);
                this.next();
            }, 6000);
        },
        stop() {
            if (this.timer) {
                clearInterval(this.timer);
                this.timer = null;
            }
        },
        init() {
            this.start();
            // Pause on tab visibility hidden, resume on visible
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) this.stop(); else this.start();
            });
        },
        next() { this.index = (this.index + 1) % this.count },
        prev() { this.index = (this.index - 1 + this.count) % this.count },
        go(i) { this.index = i; },
    }"
    x-init="init()"
    class="relative mb-8"
    @mouseenter="stop()"
    @mouseleave="start()"
    role="region"
    aria-roledescription="carousel"
    aria-label="{{ __('announcement::public.carousel.region_label') }}"
>
    <div class="overflow-hidden rounded-lg">
        <ul
            class="flex transition-transform duration-500 ease-out"
            :style="`width: ${count * 100}%; transform: translateX(-${(index * 100) / count}%);`"
            @keydown.left.prevent="prev()"
            @keydown.right.prevent="next()"
            tabindex="0"
            aria-live="polite"
        >
            @foreach($items as $i => $item)
                <li class="relative" :style="`width: ${100 / count}%`" :aria-hidden="index !== {{ $i }}">
                    <a href="{{ route('announcements.show', $item->slug) }}" class="block relative">
                        @php
                            $base = asset('storage/'.$item->header_image_path);
                            $path = pathinfo($item->header_image_path ?? '', PATHINFO_DIRNAME);
                            $name = pathinfo($item->header_image_path ?? '', PATHINFO_FILENAME);
                        @endphp
                        <picture>
                            <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                            <img
                                class="w-full h-56 md:h-72 object-cover"
                                src="{{ $base }}"
                                srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                                sizes="(max-width: 640px) 100vw, 800px"
                                alt="{{ $item->title }}"
                                loading="eager"
                            >
                        </picture>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-4 text-white">
                            <h3 class="text-xl font-semibold">{{ $item->title }}</h3>
                            <p class="opacity-90 line-clamp-2">{{ $item->summary }}</p>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    </div>

    <button type="button" @click="prev()" class="absolute inset-y-0 left-2 my-auto h-10 w-10 rounded-full bg-white/80 hover:bg-white shadow flex items-center justify-center" aria-label="{{ __('announcement::public.carousel.prev') }}">
        <span aria-hidden="true">‹</span>
    </button>
    <button type="button" @click="next()" class="absolute inset-y-0 right-2 my-auto h-10 w-10 rounded-full bg-white/80 hover:bg-white shadow flex items-center justify-center" aria-label="{{ __('announcement::public.carousel.next') }}">
        <span aria-hidden="true">›</span>
    </button>

    <div class="absolute bottom-2 inset-x-0 flex items-center justify-center gap-2">
        @foreach($items as $i => $item)
            <button type="button" class="h-2 w-2 rounded-full"
                :class="index === {{ $i }} ? 'bg-white' : 'bg-white/50'"
                @click="go({{ $i }})"
                :aria-current="index === {{ $i }} ? 'true' : 'false'"
                aria-label="{{ __('announcement::public.carousel.goto', ['number' => $i + 1]) }}"></button>
        @endforeach
    </div>
</div>
