@props(['fanart'])

{{-- Clicking asks the story page's viewer modal to load the viewer fragment. --}}
<button type="button"
        class="block w-40 h-40 overflow-hidden rounded surface-neutral"
        data-fanart-id="{{ $fanart->id }}"
        aria-haspopup="dialog"
        x-on:click="$dispatch('fanart-open', { url: '{{ route('fanart.show', $fanart->id) }}' })">
    @if ($fanart->kind === \App\Domains\Fanart\Private\Support\FanartKind::Audio)
        <span class="flex flex-col items-center justify-center w-full h-full gap-1 p-2 text-sm">
            <span class="material-symbols-outlined text-[40px]" aria-hidden="true">volume_up</span>
            <span class="line-clamp-2">{{ $fanart->title }}</span>
        </span>
    @else
        <img src="{{ route('fanart.thumb', [$fanart->id, 400]) }}"
             srcset="{{ route('fanart.thumb', [$fanart->id, 400]) }} 400w, {{ route('fanart.thumb', [$fanart->id, 800]) }} 800w"
             sizes="160px"
             loading="lazy"
             alt="{{ $fanart->title }}"
             class="w-full h-full object-cover">
    @endif
</button>
