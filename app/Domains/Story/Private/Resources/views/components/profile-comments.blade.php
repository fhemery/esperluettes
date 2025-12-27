@if(!$isAllowed)
{{-- Component should not be rendered for non-confirmed users --}}
@elseif(!$hasComments)
<div class="text-center text-gray-500 py-8">
    {{ __('story::profile.no-comments') }}
</div>
@else
<div class="flex flex-col gap-4">
    @foreach($authorGroups as $authorGroup)
    {{-- Author(s) collapsible --}}
    <x-shared::collapsible color="secondary">
        <x-slot:header>
            <div class="flex items-center gap-3">
                {{-- Display all author avatars --}}
                <div class="flex -space-x-2">
                    @foreach($authorGroup->authors as $author)
                        <x-shared::avatar :src="$author->avatar_url" :alt="$author->display_name" class="h-8 w-8 ring-2 ring-white" />
                    @endforeach
                </div>
                {{-- Display all author names separated by commas --}}
                <span class="font-semibold">
                    @foreach($authorGroup->authors as $index => $author){{ $index > 0 ? ', ' : '' }}{{ $author->display_name }}@endforeach
                </span>
                <span class="text-gray-500 text-sm">({{ trans_choice('story::profile.comments-count', $authorGroup->totalCommentCount, ['count' => $authorGroup->totalCommentCount]) }})</span>
            </div>
        </x-slot:header>

        <div class="flex flex-col gap-2">
            @foreach($authorGroup->stories as $story)
            {{-- Story collapsible with async comment loading --}}
            <div x-data="{
                open: false,
                comments: [],
                loading: false,
                loaded: false,
                async fetchComments() {
                    if (this.loaded) return;
                    this.loading = true;
                    try {
                        const response = await fetch('{{ route('profile.comments.api', ['storyId' => $story->id, 'userId' => $profileUserId]) }}');
                        const data = await response.json();
                        this.comments = data.comments;
                        this.loaded = true;
                    } catch (e) {
                        console.error('Failed to load comments', e);
                    } finally {
                        this.loading = false;
                    }
                }
            }" class="border border-secondary">
                <button type="button"
                    class="w-full flex items-center justify-between px-4 py-2 text-left bg-read"
                    @click="open = !open; if (open && !loaded) fetchComments()">
                    <div class="flex items-center gap-3">
                        <x-shared::default-cover class="h-12 w-auto object-contain" />
                        <span class="font-semibold">{{ $story->title }}</span>
                        <span class="text-gray-500 text-sm">({{ trans_choice('story::profile.comments-count', $story->commentCount, ['count' => $story->commentCount]) }})</span>
                    </div>
                    <span class="material-symbols-outlined text-secondary transition-transform duration-200"
                          :class="open ? 'rotate-180' : ''">expand_less</span>
                </button>

                <div x-show="open" x-collapse class="border-t border-secondary p-4">
                    {{-- Loading state --}}
                    <div x-show="loading" class="flex items-center justify-center py-4">
                        <span class="material-symbols-outlined animate-spin">progress_activity</span>
                        <span class="ml-2">{{ __('story::profile.loading-comments') }}</span>
                    </div>

                    {{-- Comments list (chapters) --}}
                    <div x-show="!loading && loaded" class="flex flex-col gap-2">
                        <template x-for="(comment, index) in comments" :key="comment.chapterSlug">
                            <div x-data="{ chapterOpen: false }" class="border border-secondary bg-white">
                                <button type="button"
                                    class="w-full flex items-center justify-between px-4 py-2 text-left"
                                    @click="chapterOpen = !chapterOpen">
                                    <a :href="'/stories/' + comment.storySlug + '/chapters/' + comment.chapterSlug"
                                        class="font-semibold text-accent hover:underline"
                                        x-text="comment.chapterTitle"
                                        @click.stop></a>
                                    <span class="material-symbols-outlined text-secondary transition-transform duration-200"
                                        :class="chapterOpen ? 'rotate-180' : ''">expand_less</span>
                                </button>
                                <div x-show="chapterOpen" x-collapse class="border-t border-secondary p-4">
                                    <div class="prose prose-sm max-w-none text-gray-700" x-html="comment.body"></div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </x-shared::collapsible>
    @endforeach
</div>
@endif