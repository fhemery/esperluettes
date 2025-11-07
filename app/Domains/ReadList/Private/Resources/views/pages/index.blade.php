<x-shared::app-layout>
    <x-shared::title icon="bookmark">{{ __('readlist::page.title') }}</x-shared::title>

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
</script>
