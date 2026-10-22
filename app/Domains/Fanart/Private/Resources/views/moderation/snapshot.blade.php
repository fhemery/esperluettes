<div class="space-y-1">
    @if ($mediaUrl)
        @if (($snapshot['kind'] ?? '') === 'audio')
            <audio controls src="{{ $mediaUrl }}"></audio>
        @else
            <img src="{{ $mediaUrl }}" alt="{{ $snapshot['title'] ?? '' }}" class="max-h-80 max-w-full">
        @endif
    @endif
    <div>{{ __('fanart::moderation.title') }}{{ $snapshot['title'] ?? '' }}</div>
    @if (! empty($snapshot['description']))
        <div>{{ __('fanart::moderation.description') }}{!! $snapshot['description'] !!}</div>
    @endif
    <div>{{ __('fanart::moderation.kind') }}{{ ($snapshot['kind'] ?? '') === 'audio' ? __('fanart::moderation.kind_audio') : __('fanart::moderation.kind_image') }}</div>
    <div>{{ __('fanart::moderation.status') }}{{ ($snapshot['status'] ?? '') === 'pending' ? __('fanart::moderation.status_pending') : __('fanart::moderation.status_published') }}</div>
    <div>{{ __('fanart::moderation.artist') }}{{ $snapshot['artist_name'] ?? '' }}</div>
    <div>{{ __('fanart::moderation.story') }}{{ $snapshot['story_title'] ?? '' }}</div>
    @if (! empty($snapshot['chapter_title']))
        <div>{{ __('fanart::moderation.chapter') }}{{ $snapshot['chapter_title'] }}</div>
    @endif
</div>
