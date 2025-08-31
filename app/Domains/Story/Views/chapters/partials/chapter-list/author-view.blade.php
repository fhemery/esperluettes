<section class="mt-10"
         x-data="chapterReorder({
            initial: [],
            reorderUrl: @js(route('chapters.reorder', ['storySlug' => $story->slug])),
         })"
         data-success-msg="{{ __('story::chapters.reorder_success') }}"
>
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-semibold">{{ __('story::chapters.sections.chapters') }}</h2>
        <div class="flex items-center gap-2">
            <template x-if="!editing">
                <div class="flex items-center gap-2">
                    <button type="button" @click="start()"
                            class="inline-flex items-center gap-1 px-3 py-2 rounded-md border text-gray-700 hover:bg-gray-50"
                            title="{{ __('story::chapters.actions.reorder') }}">
                        <span class="material-symbols-outlined text-[18px] leading-none">swap_vert</span>
                        {{ __('story::chapters.actions.reorder') }}
                    </button>
                    <a href="{{ route('chapters.create', ['storySlug' => $story->slug]) }}"
                       class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700">
                        <span class="material-symbols-outlined text-[18px] leading-none">add</span>
                        {{ __('story::chapters.sections.add_chapter') }}
                    </a>
                </div>
            </template>
            <template x-if="editing">
                <div class="flex items-center gap-2">
                    <button type="button" @click="save()" :disabled="saving"
                            class="inline-flex items-center gap-1 px-3 py-2 rounded-md bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50">
                        <span class="material-symbols-outlined text-[18px] leading-none" x-show="!saving">save</span>
                        <span class="material-symbols-outlined text-[18px] leading-none animate-spin" x-show="saving">progress_activity</span>
                        {{ __('story::chapters.actions.save_order') }}
                    </button>
                    <button type="button" @click="cancel()"
                            class="inline-flex items-center gap-1 px-3 py-2 rounded-md border text-gray-700 hover:bg-gray-50">
                        <span class="material-symbols-outlined text-[18px] leading-none">close</span>
                        {{ __('story::chapters.actions.cancel') }}
                    </button>
                </div>
            </template>
        </div>
    </div>

    @php($chapters = $chapters ?? ($viewModel->chapters ?? []))
    @if (empty($chapters))
        <p class="text-sm text-gray-600">{{ __('story::chapters.list.empty') }}</p>
    @else
        <div x-show="!editing">
            @include('story::chapters.partials.chapter-list.author-list', ['story' => $story, 'chapters' => $chapters])
        </div>
        <div x-show="editing">
            @include('story::chapters.partials.chapter-list.reorder-list', ['story' => $story, 'chapters' => $chapters])
        </div>
    @endif

    <div class="mt-2 text-sm rounded-md px-3 py-2 border"
         x-show="status"
         x-text="status"
         :class="statusType === 'success' ? 'bg-green-50 text-green-700 border-green-200' : (statusType === 'error' ? 'bg-red-50 text-red-700 border-red-200' : 'bg-gray-50 text-gray-700 border-gray-200')"
    ></div>
</section>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chapterReorder', ({initial, reorderUrl}) => ({
            editing: false,
            saving: false,
            status: '',
            statusType: '',
            successMsg: '',
            items: initial,
            dragIndex: null,
            init() {
                this.successMsg = this.$root?.dataset?.successMsg || 'Saved';
                // Build items from the server-rendered readonly list to avoid embedding JSON in HTML
                if (!this.items || this.items.length === 0) {
                    const ul = this.$refs.readonlyList;
                    if (ul) {
                        const lis = Array.from(ul.querySelectorAll('li[data-id]'));
                        this.items = lis.map(li => {
                            const d = li.dataset;
                            return {
                                id: parseInt(d.id),
                                title: d.title || '',
                                slug: d.slug || '',
                                url: d.url || '#',
                                isDraft: d.isDraft === '1' || d.isDraft === 'true',
                                readsLogged: parseInt(d.readsLogged || '0'),
                                editUrl: d.editUrl || '#',
                                deleteUrl: d.deleteUrl || '#',
                            };
                        });
                        // Set initial snapshot for cancel()
                        initial = this.items.slice();
                    }
                }
            },
            start() {
                this.editing = true;
                this.status = '';
                this.statusType = '';
            },
            cancel() {
                this.items = initial.slice();
                this.syncDom();
                this.editing = false;
                this.status = '';
                this.statusType = '';
            },
            indexById(id) {
                return this.items.findIndex(i => i.id === id);
            },
            onDragStartId(e, id) {
                if (!this.editing) return;
                this.dragIndex = this.indexById(id);
                e.dataTransfer.effectAllowed = 'move';
            },
            onDragOverId(e, id) {
                if (!this.editing) return;
                e.preventDefault();
            },
            onDropId(e, id) {
                if (!this.editing) return;
                const from = this.dragIndex;
                const to = this.indexById(id);
                if (from === null || to === null || from === to) return;
                const moved = this.items.splice(from, 1)[0];
                this.items.splice(to, 0, moved);
                this.dragIndex = null;
                this.syncDom();
            },
            syncDom() {
                const ul = this.$refs.list;
                if (!ul) return;
                const byId = new Map(Array.from(ul.children).map(li => [parseInt(li.getAttribute('data-id')), li]));
                this.items.forEach(it => {
                    const li = byId.get(it.id);
                    if (li) ul.appendChild(li);
                });
            },
            moveById(id, delta) {
                if (!this.editing) return;
                const i = this.indexById(id);
                const j = i + delta;
                if (i < 0 || j < 0 || j >= this.items.length) return;
                const [moved] = this.items.splice(i, 1);
                this.items.splice(j, 0, moved);
                this.syncDom();
            },
            moveUpId(id) { this.moveById(id, -1); },
            moveDownId(id) { this.moveById(id, 1); },
            async save() {
                this.saving = true;
                this.status = '';
                try {
                    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    const res = await fetch(reorderUrl, {
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
                    initial = this.items.slice();
                    this.editing = false;
                    this.status = this.successMsg;
                    this.statusType = 'success';
                    setTimeout(() => { this.status = ''; this.statusType = ''; }, 3000);
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
