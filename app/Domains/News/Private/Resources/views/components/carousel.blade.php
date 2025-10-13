@php
$size = $size ?? null;
$compact = $size === 'compact';
$items = $items ?? collect();
@endphp
@if(($items instanceof \Illuminate\Support\Collection ? $items->count() : count($items)) > 0)
<div
    x-data="newsCarousel({{ ($items instanceof \Illuminate\Support\Collection ? $items->count() : count($items)) }})"
    x-init="init()"
    class="relative"
    @mouseenter="stop()"
    @mouseleave="start()"
    role="region"
    aria-roledescription="carousel"
    aria-label="{{ __('news::public.carousel.region_label') }}">
    <div class="overflow-hidden">
        <ul
            class="flex transition-transform duration-500 ease-out"
            :style="`width: ${count * 100}%; transform: translateX(-${(index * 100) / count}%);`"
            @keydown.left.prevent="prev()"
            @keydown.right.prevent="next()"
            tabindex="0"
            aria-live="polite">
            @foreach($items as $i => $item)
            <li class="relative" :style="`width: ${100 / count}%`" :aria-hidden="index !== {{ $i }}">

                @php
                $base = asset('storage/'.$item->header_image_path);
                $path = pathinfo($item->header_image_path ?? '', PATHINFO_DIRNAME);
                $name = pathinfo($item->header_image_path ?? '', PATHINFO_FILENAME);
                @endphp
                @if($compact)
                <a href="{{ route('news.show', $item->slug) }}" class="block relative">
                    <div class="relative w-full overflow-hidden min-h-[128px]" style="padding-bottom:10%">
                        <picture>
                            <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                            <img
                                class="absolute inset-0 w-full h-full object-cover"
                                src="{{ $base }}"
                                srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                                sizes="(max-width: 640px) 100vw, 800px"
                                alt="{{ $item->title }}"
                                loading="eager">
                        </picture>
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-4 pl-16 text-white">
                            <h3 class="text-2xl leading-7 font-semibold">{{ $item->title }}</h3>
                            <p class="text-lg opacity-90 leading-6 line-clamp-2">{{ $item->summary }}</p>
                        </div>
                    </div>
                </a>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:px-24">
                    <a href="{{ route('news.show', $item->slug) }}" class="block relative">
                        <picture>
                            <source type="image/webp" srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.webp') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.webp') }} 400w">
                            <img
                                class="w-full h-56 md:h-72 object-cover"
                                src="{{ $base }}"
                                srcset="{{ asset('storage/'.$path.'/'.$name.'-800w.jpg') }} 800w, {{ asset('storage/'.$path.'/'.$name.'-400w.jpg') }} 400w"
                                sizes="(max-width: 640px) 100vw, 800px"
                                alt="{{ $item->title }}"
                                loading="eager">
                        </picture>
                    </a>
                    <div class="flex flex-col gap-4 h-full pb-4">
                        <a href="{{ route('news.show', $item->slug) }}" class="block relative">
                            <x-shared::title tag="h3" class="text-xl text-secondary">{{ $item->title }}</x-shared::title>
                        </a>
                        <p class="hidden sm:block sm:flex-1">{{ $item->summary }}</p>
                        <p class="hidden sm:block text-sm" x-data="{updatedAt: '{{ $item->updated_at }}'}">{{__('news::public.index.updated_at')}}
                            <span x-text="DateUtils.formatDate(updatedAt)"></span>
                        </p>
                    </div>
                    @endif
            </li>
            @endforeach
        </ul>
    </div>

    <button type="button" @click="prev()" class="surface-accent text-on-surface hover:bg-accent/90 absolute inset-y-0 left-2 my-auto h-10 w-10 rounded-full flex items-center justify-center" aria-label="{{ __('news::public.carousel.prev') }}">
        <span class="material-symbols-outlined" aria-hidden="true">chevron_left</span>
    </button>
    <button type="button" @click="next()" class="surface-accent text-on-surface hover:bg-accent/90 absolute inset-y-0 right-2 my-auto h-10 w-10 rounded-full flex items-center justify-center" aria-label="{{ __('news::public.carousel.next') }}">
        <span class="material-symbols-outlined" aria-hidden="true">chevron_right</span>
    </button>

    <div class="absolute bottom-2 inset-x-0 flex items-center justify-center gap-2">
        @foreach($items as $i => $item)
        <button type="button" class="h-2 w-2 rounded-full"
            :class="index === {{ $i }} ? 'bg-accent' : 'bg-accent/50'"
            @click="go({{ $i }})"
            :aria-current="index === {{ $i }} ? 'true' : 'false'"
            aria-label="{{ __('news::public.carousel.goto', ['number' => $i + 1]) }}"></button>
        @endforeach
    </div>
</div>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('newsCarousel', (count) => ({
            index: 0,
            count,
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
                    if (document.hidden) this.stop();
                    else this.start();
                });
            },
            next() {
                this.index = (this.index + 1) % this.count
            },
            prev() {
                this.index = (this.index - 1 + this.count) % this.count
            },
            go(i) {
                this.index = i;
            },
        }));
    });
</script>
@endif