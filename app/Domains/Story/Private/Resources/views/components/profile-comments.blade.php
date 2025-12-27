@if(!$isAllowed)
{{-- Component should not be rendered for non-confirmed users --}}
@elseif(!$hasComments)
<div class="text-center text-gray-500 py-8">
    {{ __('story::profile.no-comments') }}
</div>
@else
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4" x-data="{ expandedStory: null }">
    @foreach($storiesWithCommentCounts as $storyData)
    <div
        x-data="{ 
            comments: [], 
            loading: false, 
            loaded: false,
            async fetchComments() {
                if (this.loaded) return;
                this.loading = true;
                try {
                    const response = await fetch('{{ route('profile.comments.api', ['storyId' => $storyData['storyId'], 'userId' => $profileUserId]) }}');
                    const data = await response.json();
                    this.comments = data.comments;
                    this.loaded = true;
                } catch (e) {
                    console.error('Failed to load comments', e);
                } finally {
                    this.loading = false;
                }
            }
        }"
        :class="expandedStory === {{ $storyData['storyId'] }} ? 'col-span-1 md:col-span-2 lg:col-span-4 w-full' : 'mx-auto'"
        class="transition-all duration-300 h-full">
        {{-- Collapsed state: card with comment count badge --}}
        <div x-show="expandedStory !== {{ $storyData['storyId'] }}" class="flex flex-col gap-2 h-full max-w-[230px]">
            <x-story::card :item="$storyData['item']" :displayAuthors="true" :light="true" class="flex-1" />
            {{-- Comment count badge with expand button --}}
            <div class="flex justify-between items-center">
                <span class="font-bold">{{ trans_choice('story::profile.comments-count', $storyData['commentCount'], ['count' => $storyData['commentCount']]) }}</span>
                <button
                    @click="expandedStory = {{ $storyData['storyId'] }}; fetchComments()"
                    class="flex items-center gap-1 px-2 py-1 bg-accent text-white text-sm font-bold hover:bg-accent/80 transition-colors"
                    title="{{ __('story::profile.view-comments') }}">
                    {{ __('story::profile.view-comments') }}
                </button>
            </div>
        </div>

        {{-- Expanded state: card + collapsible comments --}}
        <div x-show="expandedStory === {{ $storyData['storyId'] }}" x-cloak class="border border-secondary">
            <div class="flex flex-col md:flex-row gap-4 w-full">
                {{-- Story card (narrower) --}}
                <div class="flex-shrink-0 max-w-[230px] mx-auto">
                    <x-story::card :item="$storyData['item']" :displayAuthors="true" :light="true" />
                </div>

                {{-- Comments panel --}}
                <div class="flex-1 flex flex-col gap-2 w-full p-2">
                    {{-- Header with collapse button --}}
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-lg">
                            {{ trans_choice('story::profile.comments-count', $storyData['commentCount'], ['count' => $storyData['commentCount']]) }}
                        </h3>
                        <button
                            @click="expandedStory = null"
                            class="p-1 hover:bg-gray-200 rounded-full transition-colors"
                            title="{{ __('story::profile.collapse') }}">
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>

                    {{-- Loading state --}}
                    <div x-show="loading" class="flex items-center justify-center py-8">
                        <span class="material-symbols-outlined animate-spin">progress_activity</span>
                        <span class="ml-2">{{ __('story::profile.loading-comments') }}</span>
                    </div>

                    {{-- Comments list with collapsible items --}}
                    <div x-show="!loading && loaded" class="flex flex-col gap-2 max-h-[500px] overflow-y-auto">
                        <template x-for="(comment, index) in comments" :key="comment.chapterSlug">
                            <div x-data="{ open: false }" class="border border-secondary bg-read">
                                <button type="button"
                                    class="w-full flex items-center justify-between px-4 py-2 text-left"
                                    @click="open = !open">
                                    <a :href="'/stories/' + comment.storySlug + '/chapters/' + comment.chapterSlug"
                                        class="font-semibold text-accent hover:underline"
                                        x-text="comment.chapterTitle"
                                        @click.stop></a>
                                    <span class="material-symbols-outlined text-secondary transition-transform duration-200"
                                        :class="open ? 'rotate-180' : ''">expand_less</span>
                                </button>
                                <div x-show="open" x-collapse class="border-t border-secondary p-4">
                                    <div class="prose prose-sm max-w-none text-gray-700" x-html="comment.body"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif