<div>
    <div x-data="{ expanded: false }">
        <div x-show="!expanded">
            {{ __('comment::moderation.comment_body') }}{{ \Illuminate\Support\Str::limit($body, 300) }}
            <button type="button" class="text-accent underline" @click="expanded = true">{{ __('comment::moderation.see_more') }}</button>
        </div>
        <div x-show="expanded">
            {{ __('comment::moderation.comment_body') }}{!! $body !!}
            <button type="button" class="text-accent underline" @click="expanded = false">{{ __('comment::moderation.see_less') }}</button>
        </div>
    </div>
</div>
