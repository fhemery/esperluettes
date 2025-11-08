<x-shared::app-layout>
    <x-shared::title icon="bookmark">{{ __('readlist::page.title') }}</x-shared::title>

    {{-- This load is need for toggling read/unread on chapters to work, as the chunk is loaded asynchronously --}}
    @include('shared::components.read-toggle-script')

    @if ($vm->stories->count() >0)
    <div class="flex flex-col gap-4" x-data="readListInfiniteScroll()">
        {{-- Initial stories --}}
        @foreach($vm->stories as $story)
            <x-read-list::read-list-card :item="$story" />
        @endforeach

        {{-- Load more trigger --}}
        <div x-ref="loadMoreTrigger" 
             x-intersect="loadMore()"
             class="py-4">
        </div>

        {{-- Loading indicator --}}
        <div x-show="isLoading" 
             class="flex justify-center items-center py-8"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-accent"></div>
            <span class="ml-2 text-fg/70">Chargement...</span>
        </div>
    </div>
    @else
    <div class="surface-read text-on-surface flex-1 h-full w-full flex flex-col items-center justify-center gap-8">
        <div>{{ __('readlist::page.empty') }}</div>
        <x-shared::button color="accent"> {{ __('readlist::page.empty_action') }}</x-shared::button>
    </div>
    @endif
</x-shared::app-layout>

<script>
function readListInfiniteScroll() {
    return {
        currentPage: 1,
        hasMore: true,
        isLoading: false,
        
        init() {
            // Set initial hasMore based on pagination
            this.hasMore = @json($vm->pagination->current_page < $vm->pagination->last_page);
        },
        
        async loadMore() {
            if (this.isLoading || !this.hasMore) return;
            
            this.isLoading = true;
            
            try {
                const params = new URLSearchParams({
                    page: this.currentPage + 1,
                    perPage: 10
                });
                
                const response = await fetch(`{{ route('readlist.load-more') }}?${params}`);
                const data = await response.json();
                
                if (data.html) {
                    // Insert new stories before the load more trigger
                    this.$refs.loadMoreTrigger.insertAdjacentHTML('beforebegin', data.html);
                }
                
                this.currentPage = data.nextPage - 1;
                this.hasMore = data.hasMore;
                
            } catch (error) {
                console.error('Error loading more stories:', error);
            } finally {
                this.isLoading = false;
            }
        }
    }
}

function readListCard(storyId) {
    return {
        isOpen: false,
        chaptersHtml: '',
        isLoading: false,
        
        loadChapters() {
            if (this.chaptersHtml) {
                this.isOpen = !this.isOpen;
                return;
            }
            
            this.isLoading = true;
            fetch(`{{ route('readlist.chapters', ':storyId') }}`.replace(':storyId', storyId))
                .then(response => response.json())
                .then(data => {
                    this.chaptersHtml = data.html;
                    this.isOpen = true;
                    
                    // Reinitialize Alpine.js on the new content
                    this.$nextTick(() => {
                        const chaptersContainer = this.$el.querySelector('[x-html]');
                        if (chaptersContainer && chaptersContainer.firstElementChild) {
                            Alpine.initTree(chaptersContainer.firstElementChild);
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading chapters:', error);
                })
                .finally(() => {
                    this.isLoading = false;
                });
        }
    }
}

// Required functions for chapter read toggles
(function(){
    if (window.storyReadItem) return;

    function buildUrl(storySlug, chapterSlug) {
        return `/stories/${encodeURIComponent(storySlug)}/chapters/${encodeURIComponent(chapterSlug)}/read`;
    }

    window.storyReadItem = function({ storySlug, chapterSlug, csrf, isRead }) {
        return {
            isRead: !!isRead,
            async mark() {
                try {
                    const res = await fetch(buildUrl(storySlug, chapterSlug), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'text/plain',
                        },
                    });
                    if (res.status === 204) {
                        this.isRead = true;
                    }
                } catch (e) {
                }
            },
            async unmark() {
                try {
                    const res = await fetch(buildUrl(storySlug, chapterSlug), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'text/plain',
                        },
                    });
                    if (res.status === 204) {
                        this.isRead = false;
                    }
                } catch (e) {
                }
            },
        };
    };
})();
</script>
