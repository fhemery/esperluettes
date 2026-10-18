@php
    $audios = $fanarts->where('kind', \App\Domains\Fanart\Private\Support\FanartKind::Audio);
    $images = $fanarts->where('kind', \App\Domains\Fanart\Private\Support\FanartKind::Image);
@endphp
<div class="flex flex-col gap-4">
    @foreach ($audios as $audio)
        <div class="flex flex-col gap-1">
            <button type="button" class="text-left font-medium hover:text-accent"
                    x-on:click="$dispatch('fanart-open', { url: '{{ route('fanart.show', $audio->id) }}' })">
                {{ $audio->title }}
            </button>
            @if (isset($artists[$audio->user_id]))
                <span class="text-sm">{{ __('fanart::ui.viewer.artist') }} {{ $artists[$audio->user_id] }}</span>
            @endif
            <audio controls preload="none" src="{{ route('fanart.file', $audio->id) }}" class="w-full max-w-md"
                   aria-label="{{ $audio->title }}"></audio>
        </div>
    @endforeach

    @if ($images->isNotEmpty())
        <div class="flex flex-wrap gap-4">
            @foreach ($images as $image)
                <x-fanart::thumbnail :fanart="$image" />
            @endforeach
        </div>
    @endif
</div>
