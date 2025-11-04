@php($initial = (bool) ($read ?? false))
<div x-data="readToggle({ initial: {{ $initial ? 'true' : 'false' }} })">
    <button type="button"
            class="read-toggle inline-flex items-center justify-center rounded-full w-10 h-10"
            :aria-label="isRead ? labels.read : labels.unread"
            :title="isRead ? labels.read : labels.unread"
            x-on:click="toggle()">
        <span class="material-symbols-outlined text-[30px] leading-none"
              :class="isRead ? 'text-success' : 'text-gray-300'"
              :data-test-id="isRead ? 'read-toggle-icon-read' : 'read-toggle-icon-unread'">check_circle</span>
    </button>

    <script>
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
</div>
