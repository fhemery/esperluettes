@if ($visible)
    @php($link = 'inline-flex items-center justify-center rounded-md font-medium px-4 py-2 text-sm surface-accent text-on-surface border-surface hover:border-surface/90')
    <div class="flex flex-wrap items-center gap-2 mb-4" data-fanart-actions>
        @foreach ($kinds as $kind => $labelKey)
            <a href="{{ route('fanart.create', ['slug' => $storySlug, 'kind' => $kind]) }}" class="{{ $link }}">{{ __($labelKey) }}</a>
        @endforeach
        <x-fanart::request-role-button :story-id="$storyId" :label="$requestLabel" />
    </div>
@endif
