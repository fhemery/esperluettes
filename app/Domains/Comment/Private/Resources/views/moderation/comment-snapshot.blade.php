<div>
    <div x-data="{ expanded: false }">
        <div x-show="!expanded" class="truncate-2">
            {{ __('comment::moderation.comment_body') }}{!! $body !!}
            <button type="button" class="text-accent underline" @click="expanded = true">{{ __('comment::moderation.see_more') }}</button>
        </div>
        <div x-show="expanded">
            {{ __('comment::moderation.comment_body') }}{!! $body !!}
            <button type="button" class="text-accent underline" @click="expanded = false">{{ __('comment::moderation.see_less') }}</button>
        </div>
    </div>
</div>
