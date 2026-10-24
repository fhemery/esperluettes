<div class="flex flex-col gap-2">
    @if ($parent)
        <div class="border-l-4 border-gray-300 pl-3">
            <div>{{ __('comment::moderation.annotation_parent') }}</div>
            <div><span class="font-semibold">{{ __('comment::moderation.annotation_passage') }}</span> {{ $parent['highlighted_text'] }}</div>
            <div>{{ __('comment::moderation.annotation_body') }}{!! $parent['body'] !!}</div>
        </div>
    @endif
    @if ($highlightedText !== '')
        <div><span class="font-semibold">{{ __('comment::moderation.annotation_passage') }}</span> {{ $highlightedText }}</div>
    @endif
    <div>{{ __('comment::moderation.annotation_body') }}{!! $body !!}</div>
</div>
