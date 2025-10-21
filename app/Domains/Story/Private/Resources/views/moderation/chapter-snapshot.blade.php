<div>
    <div>{{ __('story::moderation.chapter_title') }}{{ $title }}</div>
    <div x-data="{ expanded: false }">
        <div x-show="!expanded">
            {{ __('story::moderation.chapter_content') }}{{ \Illuminate\Support\Str::limit($content, 300) }}
            <button type="button" class="text-accent underline" @click="expanded = true">{{ __('story::moderation.see_more') }}</button>
        </div>
        <div x-show="expanded">
            {{ __('story::moderation.chapter_content') }}{!! $content !!}
            <button type="button" class="text-accent underline" @click="expanded = false">{{ __('story::moderation.see_less') }}</button>
        </div>
    </div>
</div>
