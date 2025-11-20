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
            async deactivateUser(url) {
                if (!confirm('{{ __('moderation::admin.user_management.confirm_deactivate') }}')) {
                    return;
                }

                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                    });

                    if (response.ok) {
                        this.showMessage('{{ __('moderation::admin.user_management.deactivated_success') }}', 'success');
                        this.onSearchInput(); // Refresh the search results
                    } else {
                        this.showMessage('{{ __('moderation::admin.user_management.deactivated_error') }}', 'error');
                    }
                } catch (error) {
                    console.error('Error deactivating user:', error);
                    this.showMessage('{{ __('moderation::admin.user_management.network_error') }}', 'error');
                }
            },
            async activateUser(url) {
                if (!confirm('{{ __('moderation::admin.user_management.confirm_activate') }}')) {
                    return;
                }

                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const response = await fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                    });

                    if (response.ok) {
                        this.showMessage('{{ __('moderation::admin.user_management.activated_success') }}', 'success');
                        this.onSearchInput(); // Refresh the search results
                    } else {
                        this.showMessage('{{ __('moderation::admin.user_management.activated_error') }}', 'error');
                    }
                } catch (error) {
                    console.error('Error activating user:', error);
                    this.showMessage('{{ __('moderation::admin.user_management.network_error') }}', 'error');
                }
            },
            showMessage(message, type) {
                // Simple alert for now - could be enhanced with a toast component
                alert(message);
            },
        };
    }
</script>

</x-admin::layout>