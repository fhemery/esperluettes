@props(['quoteList', 'isOwn' => false])
<div class="flex flex-col gap-6">
    @if($quoteList->totalCount === 0)
        <p class="text-center text-gray-500 py-8">
            {{ $isOwn ? __('quote::ui.profile_tab.empty_own') : __('quote::ui.profile_tab.empty_other') }}
        </p>
    @else
        @foreach($quoteList->items as $quote)
        <article class="flex flex-col gap-2 border-b border-accent pb-4 last:border-0 last:pb-0">
            <blockquote class="border-l-4 border-tertiary/60 pl-3 italic text-fg/80 text-sm">
                {{ $quote->highlightedText }}
            </blockquote>

            @if($quote->note)
            <div class="prose prose-sm text-fg/70">
                {!! $quote->note !!}
            </div>
            @endif

            <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-fg/50">
                @if($quote->chapterAvailable && $quote->chapterUrl)
                    <a href="{{ $quote->chapterUrl }}"
                       class="text-accent hover:underline">
                        {{ $quote->chapterTitle }}
                    </a>
                    <span aria-hidden="true">·</span>
                    <a href="{{ $quote->storyUrl }}"
                       class="hover:underline">
                        {{ $quote->storyTitle }}
                    </a>
                @else
                    <span>{{ $quote->storyTitle }}</span>
                @endif

                @if(!empty($quote->authorProfiles))
                    <span aria-hidden="true">·</span>
                    <span>
                        @foreach($quote->authorProfiles as $i => $author)
                            @if($i > 0), @endif
                            <a href="{{ route('profile.show', $author->slug) }}"
                               class="hover:underline">{{ $author->display_name }}</a>
                        @endforeach
                    </span>
                @endif
            </div>
        </article>
        @endforeach

        @if($quoteList->totalCount > count($quoteList->items))
        <div class="text-center text-sm text-fg/50 pt-2">
            {{ __('quote::ui.profile_tab.showing', ['count' => count($quoteList->items), 'total' => $quoteList->totalCount]) }}
        </div>
        @endif
    @endif
</div>
