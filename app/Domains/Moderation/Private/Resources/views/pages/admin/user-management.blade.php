<x-admin::layout>
    <x-shared::title>{{ __('moderation::admin.user_management.title') }}</x-shared::title>
    
    <div x-data="userManagement()" x-init="init()">
    <div class="mb-6">
        <input
            type="text"
            x-model="searchQuery"
            @input.debounce.300ms="onSearchInput()"
            placeholder="{{ __('moderation::admin.user_management.search_instruction') }}"
            class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
        >
    </div>

    <div x-html="resultsHtml"></div>
</div>

<script>
    function userManagement() {
        return {
            searchQuery: '',
            resultsHtml: '',
            init() {
                this.resultsHtml = '';
            },
            onSearchInput() {
                this.resultsHtml = '';
                fetch(`{{ route('moderation.admin.search') }}?q=${this.searchQuery}`)
                    .then(response => response.text())
                    .then(html => {
                        this.resultsHtml = html;
                    })
                    .catch(error => {
                        console.error('Error fetching search results:', error);
                    });
            },
        };
    }
</script>

</x-admin::layout>