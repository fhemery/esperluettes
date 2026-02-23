@props([
    'coverType' => 'default',
    'coverUrl' => null,
    'coverHdUrl' => null,
    'hd' => false,
    'width' => 150,
    'class' => '',
    'authorNames' => [],
    'storyTitle' => null,
])

@php
    $widthClass = match ((int) $width) {
        300 => 'w-[300px]',
        230 => 'w-[230px]',
        default => 'w-[150px]',
    };
    $canLightbox = $hd && $coverHdUrl && $coverType !== 'default';
    $showText = $coverType === 'themed' && $storyTitle;
    $authorsText = !empty($authorNames) ? implode(', ', $authorNames) : '';
@endphp

<div {{ $attributes->merge(['class' => "{$widthClass} {$class}"]) }} aria-hidden="true"
    @if($canLightbox) x-data="{ lightboxOpen: false }" @endif
>
    @if ($coverType === 'default')
        <x-shared::default-cover class="{{ $widthClass }} object-contain" />
    @elseif($canLightbox)
        <div class="relative {{ $widthClass }}">
            <img src="{{ $coverUrl }}" alt="" class="{{ $widthClass }} object-contain cursor-pointer"
                loading="lazy" @click="lightboxOpen = true" />
            
            @if($showText)
                <div class="absolute inset-0 pointer-events-none flex flex-col text-read" style="padding: 20px 22px 0 22px;">
                    @if($authorsText)
                        <div class="text-center uppercase overflow-hidden text-ellipsis whitespace-nowrap pb-1" 
                            style="font-family: 'Aptos', sans-serif; font-size: 9px; font-weight: 400; letter-spacing: 0.02em;">
                            {{ $authorsText }}
                        </div>
                    @endif
                    @if($storyTitle)
                        <div class="text-center uppercase" 
                            style="font-family: 'Aptos', sans-serif; font-size: 18px; font-weight: 600; line-height: 0.85; 
                                   display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $storyTitle }}
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Lightbox overlay --}}
        <template x-teleport="body">
            <div x-show="lightboxOpen" x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-black/70"
                @click.self="lightboxOpen = false"
                @keydown.escape.window="lightboxOpen = false"
                x-data="{
                    ratio: 1,
                    updateRatio() {
                        const img = this.$refs.hdImage;
                        if (img && img.complete && img.offsetWidth > 0) {
                            this.ratio = img.offsetWidth / 230;
                        }
                    }
                }"
                x-init="$watch('lightboxOpen', value => { if (value) { $nextTick(() => updateRatio()) } })"
                @resize.window="updateRatio()">
                <div class="relative max-w-[min(800px,95vw)] max-h-[95vh]">
                    <img x-ref="hdImage" 
                        src="{{ $coverHdUrl }}" alt=""
                        class="max-w-[min(800px,95vw)] max-h-[95vh] object-contain"
                        loading="lazy"
                        @load="updateRatio()" />
                    
                    @if($showText)
                        {{-- Scale text proportionally based on actual image width ratio --}}
                        <div class="absolute inset-0 pointer-events-none flex flex-col text-read" 
                            :style="`padding: ${20 * ratio}px ${22 * ratio}px 0 ${22 * ratio}px;`">
                            @if($authorsText)
                                <div class="text-center uppercase overflow-hidden text-ellipsis whitespace-nowrap pb-1" 
                                    :style="`font-family: 'Aptos', sans-serif; font-size: ${9 * ratio}px; font-weight: 400; letter-spacing: 0.02em;`">
                                    {{ $authorsText }}
                                </div>
                            @endif
                            @if($storyTitle)
                                <div class="text-center uppercase" 
                                    :style="`font-family: 'Aptos', sans-serif; font-size: ${18 * ratio}px; font-weight: 600; line-height: 0.85;
                                           display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;`">
                                    {{ $storyTitle }}
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </template>
    @else
        <div class="relative {{ $widthClass }}">
            <img src="{{ $coverUrl }}" alt="" class="{{ $widthClass }} object-contain" loading="lazy" />
            
            @if($showText)
                 <div class="absolute inset-0 pointer-events-none flex flex-col text-read" style="padding: 20px 22px 0 22px;">
                    @if($authorsText)
                        <div class="text-center uppercase overflow-hidden text-ellipsis whitespace-nowrap pb-1" 
                            style="font-family: 'Aptos', sans-serif; font-size: 9px; font-weight: 400; letter-spacing: 0.02em;">
                            {{ $authorsText }}
                        </div>
                    @endif
                    @if($storyTitle)
                        <div class="text-center uppercase" 
                            style="font-family: 'Aptos', sans-serif; font-size: 18px; font-weight: 600; line-height: 0.85; 
                                   display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            {{ $storyTitle }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
