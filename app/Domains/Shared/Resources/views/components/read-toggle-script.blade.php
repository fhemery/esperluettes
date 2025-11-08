{{-- This script is need for other places of the software (e.g. readlist) and must be preloaded.
     I have yet to find a smarter way of preloading, but JS bundling attempts failed.
     --}}

@once
@push('scripts')
<script>
    // Preload readToggle function for dynamic content
    function readToggle({ initial }) {
        return {
            isRead: !!initial,
            labels: {
                read: '{{ __("shared::components.read_toggle.marked_read") }}',
                unread: '{{ __("shared::components.read_toggle.mark_as_read") }}',
            },
            toggle() {
                if (!this.isRead) {
                    this.isRead = true;
                    this.$dispatch('markRead');
                    this.$dispatch('mark-read');
                } else {
                    this.isRead = false;
                    this.$dispatch('markUnread');
                    this.$dispatch('mark-unread');
                }
            },
        }
    }
</script>
@endpush
@endonce
