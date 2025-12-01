{{-- 
    Reorderable List Component
    
    This component displays a reorderable list with drag-and-drop and arrow buttons.
    It is meant to be shown/hidden by a parent component that toggles between
    a full details view and this reorder view.
    
    Props:
    - items: Collection/array with id and name fields
    - reorderUrl: URL for PUT request with ordered_ids
    - nameField: Field to display as name (default: 'name')
    - eventName: Custom event name prefix for save/cancel communication
    
    Events dispatched:
    - {eventName}-save: When save completes successfully
    - {eventName}-cancel: When user clicks cancel
--}}
@props([
    'items' => [],
    'reorderUrl' => '',
    'nameField' => 'name',
    'eventName' => 'reorder',
])

@php
    $initItems = collect($items)->map(fn($item) => [
        'id' => (int) $item->id,
        'name' => (string) $item->{$nameField},
    ])->values();
@endphp

<div x-data="adminReorderList({ 
    items: @js($initItems), 
    reorderUrl: @js($reorderUrl),
    eventName: @js($eventName)
})" class="flex flex-col gap-3">
    
    <!-- Action buttons -->
    <div class="flex items-center justify-end gap-2">
        <x-shared::button color="primary" icon="save" x-on:click="save()" x-bind:disabled="saving">
            <span x-show="saving" class="animate-spin material-symbols-outlined text-[18px]">progress_activity</span>
            <span x-show="!saving">{{ __('administration::reorder.save') }}</span>
        </x-shared::button>
        <x-shared::button color="neutral" icon="close" x-on:click="cancel()">
            {{ __('administration::reorder.cancel') }}
        </x-shared::button>
    </div>

    <!-- Error message -->
    <div x-show="status" x-transition class="p-3 rounded text-sm bg-error/10 text-error">
        <span x-text="status"></span>
    </div>

    @if($initItems->isEmpty())
        <p class="text-fg/60 text-sm p-4">{{ __('administration::reorder.empty') }}</p>
    @else
        <!-- Reorderable list -->
        <div class="surface-read text-on-surface overflow-hidden">
            <ul class="divide-y divide-border">
                <template x-for="(it, idx) in items" :key="it.id">
                    <li class="p-3 flex items-center justify-between gap-3 hover:bg-surface-read/50"
                        draggable="true"
                        @dragstart="onDragStart($event, it.id)"
                        @dragover.prevent
                        @drop.prevent="onDrop($event, it.id)"
                        @dragend="dragIndex = null"
                        :class="{ 'opacity-50': dragIndex !== null && indexById(it.id) === dragIndex }"
                    >
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-[18px] text-fg/40 cursor-grab active:cursor-grabbing [@media(pointer:coarse)]:hidden" 
                                  title="{{ __('administration::reorder.drag') }}">
                                drag_indicator
                            </span>
                            <span class="text-fg/50 w-6 text-center" x-text="idx + 1"></span>
                            <span class="font-medium" x-text="it.name"></span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button"
                                    class="inline-flex items-center justify-center h-8 w-8 rounded border border-border text-fg/60 hover:bg-surface-read disabled:opacity-40 disabled:cursor-not-allowed"
                                    @click="moveUp(it.id)"
                                    :disabled="idx === 0"
                                    title="{{ __('administration::reorder.move_up') }}"
                                    aria-label="{{ __('administration::reorder.move_up') }}">
                                <span class="material-symbols-outlined text-[18px]">arrow_upward</span>
                            </button>
                            <button type="button"
                                    class="inline-flex items-center justify-center h-8 w-8 rounded border border-border text-fg/60 hover:bg-surface-read disabled:opacity-40 disabled:cursor-not-allowed"
                                    @click="moveDown(it.id)"
                                    :disabled="idx === items.length - 1"
                                    title="{{ __('administration::reorder.move_down') }}"
                                    aria-label="{{ __('administration::reorder.move_down') }}">
                                <span class="material-symbols-outlined text-[18px]">arrow_downward</span>
                            </button>
                        </div>
                    </li>
                </template>
            </ul>
        </div>
    @endif
</div>

@once
@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adminReorderList', ({ items, reorderUrl, eventName }) => ({
            items: Array.isArray(items) ? items.slice() : [],
            initialSnap: [],
            dragIndex: null,
            saving: false,
            status: '',
            reorderUrl,
            eventName,

            init() {
                this.initialSnap = this.items.slice();
            },

            cancel() {
                this.items = this.initialSnap.slice();
                this.$dispatch(`${this.eventName}-cancel`);
            },

            indexById(id) { 
                return this.items.findIndex(i => i.id === id); 
            },

            onDragStart(e, id) { 
                this.dragIndex = this.indexById(id); 
                e.dataTransfer.effectAllowed = 'move'; 
            },

            onDrop(e, id) {
                const from = this.dragIndex;
                const to = this.indexById(id);
                if (from === null || to === null || from === to) return;
                const moved = this.items.splice(from, 1)[0];
                this.items.splice(to, 0, moved);
                this.dragIndex = null;
            },

            move(id, delta) {
                const i = this.indexById(id);
                const j = i + delta;
                if (i < 0 || j < 0 || j >= this.items.length) return;
                const [moved] = this.items.splice(i, 1);
                this.items.splice(j, 0, moved);
            },
            moveUp(id) { this.move(id, -1); },
            moveDown(id) { this.move(id, 1); },

            async save() {
                this.saving = true;
                this.status = '';
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const res = await fetch(this.reorderUrl, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ ordered_ids: this.items.map(i => i.id) }),
                    });
                    if (!res.ok) {
                        const data = await res.json().catch(() => ({}));
                        throw new Error(data.message || 'Failed to reorder');
                    }
                    // Success - reload the page
                    window.location.reload();
                } catch (e) {
                    this.status = (e && e.message) ? e.message : 'Error';
                } finally {
                    this.saving = false;
                }
            },
        }));
    });
</script>
@endpush
@endonce
