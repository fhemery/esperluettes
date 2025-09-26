@php($chapters = $chapters ?? ($viewModel->chapters ?? []))
@php($initItems = collect($chapters)->map(function($c){
    return [
        'id' => (int) $c->id,
        'title' => $c->title,
        'isDraft' => (bool) $c->isDraft,
    ];
})->values())
@if (!empty($chapters))
    <div x-data="reorderList({ items: @js($initItems), reorderUrl: @js(route('chapters.reorder', ['storySlug' => $story->slug])) })"
         @chapters-reorder-save.window="save()"
         @chapters-reorder-cancel.window="cancel()"
    >
        <ul class="divide-y divide-gray-200 rounded-md border border-gray-200 bg-white">
            <template x-for="(it, idx) in items" :key="it.id">
                <li class="p-3 flex items-center justify-between gap-3"
                    draggable="true"
                    @dragstart="onDragStartId($event, it.id)"
                    @dragover.prevent="onDragOverId($event, it.id)"
                    @drop.prevent="onDropId($event, it.id)"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-[18px] leading-none text-gray-400 cursor-grab [@media(pointer:coarse)]:hidden" title="Drag">
                            drag_indicator
                        </span>
                        <span class="font-medium text-gray-700" x-text="it.title"></span>
                        <span x-show="it.isDraft" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-800 ring-1 ring-inset ring-gray-300" aria-label="{{ __('story::chapters.list.draft') }}">{{ __('story::chapters.list.draft') }}</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button type="button"
                                class="inline-flex items-center justify-center h-7 w-7 rounded border text-gray-600 hover:bg-gray-50 disabled:opacity-40"
                                @click="moveUpId(it.id)"
                                :disabled="indexById(it.id) === 0"
                                title="{{ __('story::chapters.actions.move_up') }}"
                                aria-label="{{ __('story::chapters.actions.move_up') }}">
                                <span class="material-symbols-outlined text-[18px] leading-none">arrow_upward</span>
                        </button>
                        <button type="button"
                                class="inline-flex items-center justify-center h-7 w-7 rounded border text-gray-600 hover:bg-gray-50 disabled:opacity-40"
                                @click="moveDownId(it.id)"
                                :disabled="indexById(it.id) === items.length - 1"
                                title="{{ __('story::chapters.actions.move_down') }}"
                                aria-label="{{ __('story::chapters.actions.move_down') }}">
                            <span class="material-symbols-outlined text-[18px] leading-none">arrow_downward</span>
                        </button>
                    </div>
                </li>
            </template>
        </ul>
    </div>
@endif

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('reorderList', ({ items, reorderUrl }) => ({
            items: Array.isArray(items) ? items.slice() : [],
            initialSnap: [],
            dragIndex: null,
            saving: false,
            status: '',
            statusType: '',
            reorderUrl,
            init() {
                this.initialSnap = this.items.slice();
            },
            // Exposed for parent
            cancel() {
                this.items = this.initialSnap.slice();
                this.status = '';
                this.statusType = '';
            },
            // Drag & drop helpers
            indexById(id) { return this.items.findIndex(i => i.id === id); },
            onDragStartId(e, id) { this.dragIndex = this.indexById(id); e.dataTransfer.effectAllowed = 'move'; },
            onDragOverId(e) { e.preventDefault(); },
            onDropId(e, id) {
                const from = this.dragIndex;
                const to = this.indexById(id);
                if (from === null || to === null || from === to) return;
                const moved = this.items.splice(from, 1)[0];
                this.items.splice(to, 0, moved);
                this.dragIndex = null;
            },
            moveById(id, delta) {
                const i = this.indexById(id);
                const j = i + delta;
                if (i < 0 || j < 0 || j >= this.items.length) return;
                const [moved] = this.items.splice(i, 1);
                this.items.splice(j, 0, moved);
            },
            moveUpId(id) { this.moveById(id, -1); },
            moveDownId(id) { this.moveById(id, 1); },
            async save() {
                this.saving = true;
                this.status = '';
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
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
                    await res.json();
                    window.location.reload();
                } catch (e) {
                    this.status = (e && e.message) ? e.message : 'Error';
                    this.statusType = 'error';
                } finally {
                    this.saving = false;
                }
            },
        }));
    });
</script>
